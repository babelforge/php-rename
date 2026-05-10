<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Infrastructure\MemberGraph;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\MemberGraph\Application\Source\Node\MemberGraphSourceNodeLocator;
use PhpNoobs\MemberGraph\Application\Source\Node\MemberGraphSymbolScopeFact;
use PhpNoobs\MemberGraph\Application\Source\Node\MemberGraphSymbolScopeFactCollection;
use PhpNoobs\MemberGraph\Application\Source\Node\MemberGraphSymbolScopeLocator;
use PhpNoobs\MemberGraph\Application\Source\Node\VirtualPhpSourceFileNodeMatchCollection;
use PhpNoobs\PhpRename\Domain\Rename\Conflict\RenameConflictPolicy;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnostic;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticCollection;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticSeverity;
use PhpNoobs\PhpRename\Domain\Rename\Request\ClassConstantRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\Request\ClassFqcnRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\Request\ClassRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\Request\FunctionFqcnRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\Request\FunctionRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\Request\MethodRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\Request\ParameterRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\Request\PropertyRenameRequest;

/**
 * Converts neutral `member-graph` scope facts into rename conflict diagnostics.
 */
final readonly class MemberGraphRenameConflictGuard
{
    /**
     * Reports conflicts for one method rename request.
     *
     * @param RenameDiagnosticCollection $diagnostics the diagnostics to update
     * @param MethodRenameRequest        $request     the rename request
     * @param MemberDependencyGraphBuild $build       the member graph build
     */
    public function reportMethodConflicts(
        RenameDiagnosticCollection $diagnostics,
        MethodRenameRequest $request,
        MemberDependencyGraphBuild $build,
    ): void {
        $scope = MemberGraphSymbolScopeLocator::fromBuild($build)->methodScope($request->className, $request->methodName);

        $this->reportNameConflict(
            diagnostics: $diagnostics,
            facts: $scope->methodDeclarations(),
            oldName: $request->oldName(),
            newName: $request->newName(),
            policy: $request->conflictPolicy,
            message: sprintf('The method name "%s" already exists in the resolved owner scope.', $request->newName()),
        );
    }

    /**
     * Reports conflicts for one property rename request.
     *
     * @param RenameDiagnosticCollection $diagnostics the diagnostics to update
     * @param PropertyRenameRequest      $request     the rename request
     * @param MemberDependencyGraphBuild $build       the member graph build
     */
    public function reportPropertyConflicts(
        RenameDiagnosticCollection $diagnostics,
        PropertyRenameRequest $request,
        MemberDependencyGraphBuild $build,
    ): void {
        $scope = MemberGraphSymbolScopeLocator::fromBuild($build)->propertyScope($request->className, $request->propertyName);

        $this->reportNameConflict(
            diagnostics: $diagnostics,
            facts: $scope->propertyDeclarations(),
            oldName: $request->oldName(),
            newName: $request->newName(),
            policy: $request->conflictPolicy,
            message: sprintf('The property name "$%s" already exists in the resolved owner scope.', $request->newName()),
        );
    }

    /**
     * Reports conflicts for one class-constant rename request.
     *
     * @param RenameDiagnosticCollection $diagnostics the diagnostics to update
     * @param ClassConstantRenameRequest $request     the rename request
     * @param MemberDependencyGraphBuild $build       the member graph build
     */
    public function reportClassConstantConflicts(
        RenameDiagnosticCollection $diagnostics,
        ClassConstantRenameRequest $request,
        MemberDependencyGraphBuild $build,
    ): void {
        $scope = MemberGraphSymbolScopeLocator::fromBuild($build)->classConstantScope($request->className, $request->constantName);
        $facts = new MemberGraphSymbolScopeFactCollection();

        foreach ($scope->classConstantDeclarations() as $fact) {
            $facts->add($fact);
        }

        foreach ($scope->enumCaseDeclarations() as $fact) {
            $facts->add($fact);
        }

        $this->reportNameConflict(
            diagnostics: $diagnostics,
            facts: $facts,
            oldName: $request->oldName(),
            newName: $request->newName(),
            policy: $request->conflictPolicy,
            message: sprintf('The class constant or enum case name "%s" already exists in the resolved owner scope.', $request->newName()),
        );
    }

    /**
     * Reports conflicts for one short class-like rename request.
     *
     * @param RenameDiagnosticCollection $diagnostics the diagnostics to update
     * @param ClassRenameRequest         $request     the rename request
     * @param MemberDependencyGraphBuild $build       the member graph build
     */
    public function reportClassConflicts(
        RenameDiagnosticCollection $diagnostics,
        ClassRenameRequest $request,
        MemberDependencyGraphBuild $build,
    ): void {
        $scope = MemberGraphSymbolScopeLocator::fromBuild($build)->classLikeNamespaceScope($this->namespaceName($request->className));

        $this->reportShortNameConflict(
            diagnostics: $diagnostics,
            facts: $scope->classLikeDeclarations(),
            oldName: $request->oldName(),
            newName: $request->newName(),
            policy: $request->conflictPolicy,
            message: sprintf('The class-like name "%s" already exists in the target namespace.', $request->newName()),
        );
    }

    /**
     * Reports conflicts for one class-like FQCN rename request.
     *
     * @param RenameDiagnosticCollection $diagnostics the diagnostics to update
     * @param ClassFqcnRenameRequest     $request     the rename request
     * @param MemberDependencyGraphBuild $build       the member graph build
     */
    public function reportClassFqcnConflicts(
        RenameDiagnosticCollection $diagnostics,
        ClassFqcnRenameRequest $request,
        MemberDependencyGraphBuild $build,
    ): void {
        $newName = $request->newName();
        $scope = MemberGraphSymbolScopeLocator::fromBuild($build)->classLikeNamespaceScope($this->namespaceName($newName));

        $this->reportShortNameConflict(
            diagnostics: $diagnostics,
            facts: $scope->classLikeDeclarations(),
            oldName: $this->shortName($request->oldName()),
            newName: $this->shortName($newName),
            policy: $request->conflictPolicy,
            message: sprintf('The class-like FQCN "%s" already exists.', $newName),
        );
    }

    /**
     * Reports conflicts for one short function rename request.
     *
     * @param RenameDiagnosticCollection $diagnostics the diagnostics to update
     * @param FunctionRenameRequest      $request     the rename request
     * @param MemberDependencyGraphBuild $build       the member graph build
     */
    public function reportFunctionConflicts(
        RenameDiagnosticCollection $diagnostics,
        FunctionRenameRequest $request,
        MemberDependencyGraphBuild $build,
    ): void {
        $scope = MemberGraphSymbolScopeLocator::fromBuild($build)->functionNamespaceScope($this->namespaceName($request->functionName));

        $this->reportShortNameConflict(
            diagnostics: $diagnostics,
            facts: $scope->functionDeclarations(),
            oldName: $request->oldName(),
            newName: $request->newName(),
            policy: $request->conflictPolicy,
            message: sprintf('The function name "%s" already exists in the target namespace.', $request->newName()),
        );
    }

    /**
     * Reports conflicts for one function FQCN rename request.
     *
     * @param RenameDiagnosticCollection $diagnostics the diagnostics to update
     * @param FunctionFqcnRenameRequest  $request     the rename request
     * @param MemberDependencyGraphBuild $build       the member graph build
     */
    public function reportFunctionFqcnConflicts(
        RenameDiagnosticCollection $diagnostics,
        FunctionFqcnRenameRequest $request,
        MemberDependencyGraphBuild $build,
    ): void {
        $newName = $request->newName();
        $scope = MemberGraphSymbolScopeLocator::fromBuild($build)->functionNamespaceScope($this->namespaceName($newName));

        $this->reportShortNameConflict(
            diagnostics: $diagnostics,
            facts: $scope->functionDeclarations(),
            oldName: $this->shortName($request->oldName()),
            newName: $this->shortName($newName),
            policy: $request->conflictPolicy,
            message: sprintf('The function FQCN "%s" already exists.', $newName),
        );
    }

    /**
     * Reports conflicts for one parameter rename request.
     *
     * @param RenameDiagnosticCollection $diagnostics the diagnostics to update
     * @param ParameterRenameRequest     $request     the rename request
     * @param MemberDependencyGraphBuild $build       the member graph build
     */
    public function reportParameterConflicts(
        RenameDiagnosticCollection $diagnostics,
        ParameterRenameRequest $request,
        MemberDependencyGraphBuild $build,
    ): void {
        $scope = MemberGraphSourceNodeLocator::fromBuild($build)->parameterScope(
            owner: $request->owner,
            functionLikeName: $request->functionLikeName,
            parameterName: $request->parameterName,
            parameterIndex: $request->parameterIndex,
        );

        $this->reportMatchNameConflict(
            diagnostics: $diagnostics,
            matches: $scope->parameters(),
            oldName: $request->oldName(),
            newName: $request->newName(),
            policy: $request->conflictPolicy,
            message: sprintf('The parameter name "$%s" already exists in the same signature.', $request->newName()),
        );
        $this->reportMatchNameConflict(
            diagnostics: $diagnostics,
            matches: $scope->localVariables(),
            oldName: $request->oldName(),
            newName: $request->newName(),
            policy: $request->conflictPolicy,
            message: sprintf('The local variable name "$%s" already exists in the declaring body.', $request->newName()),
        );
    }

    /**
     * Reports one fact-name conflict when present.
     *
     * @param RenameDiagnosticCollection           $diagnostics the diagnostics to update
     * @param MemberGraphSymbolScopeFactCollection $facts       the scope facts to inspect
     * @param string                               $oldName     the old symbol name
     * @param string                               $newName     the new symbol name
     * @param RenameConflictPolicy                 $policy      the conflict policy
     * @param string                               $message     the diagnostic message
     */
    private function reportNameConflict(
        RenameDiagnosticCollection $diagnostics,
        MemberGraphSymbolScopeFactCollection $facts,
        string $oldName,
        string $newName,
        RenameConflictPolicy $policy,
        string $message,
    ): void {
        if (!$this->hasFactName($facts, $oldName, $newName)) {
            return;
        }

        $diagnostics->add(new RenameDiagnostic($this->severity($policy), $message));
    }

    /**
     * Reports one fact short-name conflict when present.
     *
     * @param RenameDiagnosticCollection           $diagnostics the diagnostics to update
     * @param MemberGraphSymbolScopeFactCollection $facts       the scope facts to inspect
     * @param string                               $oldName     the old symbol name
     * @param string                               $newName     the new symbol name
     * @param RenameConflictPolicy                 $policy      the conflict policy
     * @param string                               $message     the diagnostic message
     */
    private function reportShortNameConflict(
        RenameDiagnosticCollection $diagnostics,
        MemberGraphSymbolScopeFactCollection $facts,
        string $oldName,
        string $newName,
        RenameConflictPolicy $policy,
        string $message,
    ): void {
        if (!$this->hasFactShortName($facts, $oldName, $newName)) {
            return;
        }

        $diagnostics->add(new RenameDiagnostic($this->severity($policy), $message));
    }

    /**
     * Reports one source-node match conflict when present.
     *
     * @param RenameDiagnosticCollection              $diagnostics the diagnostics to update
     * @param VirtualPhpSourceFileNodeMatchCollection $matches     the source-node matches to inspect
     * @param string                                  $oldName     the old symbol name
     * @param string                                  $newName     the new symbol name
     * @param RenameConflictPolicy                    $policy      the conflict policy
     * @param string                                  $message     the diagnostic message
     */
    private function reportMatchNameConflict(
        RenameDiagnosticCollection $diagnostics,
        VirtualPhpSourceFileNodeMatchCollection $matches,
        string $oldName,
        string $newName,
        RenameConflictPolicy $policy,
        string $message,
    ): void {
        if ($oldName === $newName || !$matches->hasName($newName)) {
            return;
        }

        $diagnostics->add(new RenameDiagnostic($this->severity($policy), $message));
    }

    /**
     * Indicates whether facts contain the requested local name.
     *
     * @param MemberGraphSymbolScopeFactCollection $facts   the scope facts to inspect
     * @param string                               $oldName the old symbol name
     * @param string                               $newName the new symbol name
     */
    private function hasFactName(MemberGraphSymbolScopeFactCollection $facts, string $oldName, string $newName): bool
    {
        if ($oldName === $newName) {
            return false;
        }

        foreach ($facts as $fact) {
            if ($fact->name === $newName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Indicates whether facts contain the requested short name.
     *
     * @param MemberGraphSymbolScopeFactCollection $facts   the scope facts to inspect
     * @param string                               $oldName the old symbol short name
     * @param string                               $newName the new symbol short name
     */
    private function hasFactShortName(MemberGraphSymbolScopeFactCollection $facts, string $oldName, string $newName): bool
    {
        if ($oldName === $newName) {
            return false;
        }

        foreach ($facts as $fact) {
            if ($this->factShortName($fact) === $newName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the short symbol name represented by one scope fact.
     *
     * @param MemberGraphSymbolScopeFact $fact the scope fact
     */
    private function factShortName(MemberGraphSymbolScopeFact $fact): string
    {
        return $fact->shortName ?? $fact->name;
    }

    /**
     * Returns the diagnostic severity for one policy.
     *
     * @param RenameConflictPolicy $policy the conflict policy
     */
    private function severity(RenameConflictPolicy $policy): RenameDiagnosticSeverity
    {
        return match ($policy) {
            RenameConflictPolicy::FAIL => RenameDiagnosticSeverity::ERROR,
            RenameConflictPolicy::REPORT => RenameDiagnosticSeverity::WARNING,
        };
    }

    /**
     * Returns the namespace part of one fully-qualified symbol name.
     *
     * @param string $name the fully-qualified symbol name
     */
    private function namespaceName(string $name): string
    {
        $parts = explode('\\', ltrim($name, '\\'));
        array_pop($parts);

        return implode('\\', $parts);
    }

    /**
     * Returns the short part of one fully-qualified symbol name.
     *
     * @param string $name the fully-qualified symbol name
     */
    private function shortName(string $name): string
    {
        $parts = explode('\\', ltrim($name, '\\'));

        return (string) end($parts);
    }
}
