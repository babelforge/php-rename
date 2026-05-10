<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Infrastructure\MemberGraph\Guard;

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
use PhpNoobs\PhpRename\Domain\Rename\Request\EnumCaseRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\Request\FunctionFqcnRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\Request\FunctionRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\Request\MethodRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\Request\ParameterRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\Request\PropertyRenameRequest;
use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Namespace_;

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
            caseSensitive: false,
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
            caseSensitive: true,
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
            caseSensitive: true,
        );
    }

    /**
     * Reports conflicts for one enum-case rename request.
     *
     * @param RenameDiagnosticCollection $diagnostics the diagnostics to update
     * @param EnumCaseRenameRequest      $request     the rename request
     * @param MemberDependencyGraphBuild $build       the member graph build
     */
    public function reportEnumCaseConflicts(
        RenameDiagnosticCollection $diagnostics,
        EnumCaseRenameRequest $request,
        MemberDependencyGraphBuild $build,
    ): void {
        $scope = MemberGraphSymbolScopeLocator::fromBuild($build)->classConstantScope($request->enumName, $request->caseName);
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
            caseSensitive: true,
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
            caseSensitive: false,
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
        $scopeLocator = MemberGraphSymbolScopeLocator::fromBuild($build);
        $scope = $scopeLocator->classLikeNamespaceScope($this->namespaceName($newName));

        $this->reportShortNameConflict(
            diagnostics: $diagnostics,
            facts: $scope->classLikeDeclarations(),
            oldName: $this->shortName($request->oldName()),
            newName: $this->shortName($newName),
            policy: $request->conflictPolicy,
            message: sprintf('The class-like FQCN "%s" already exists.', $newName),
            caseSensitive: false,
        );
        $this->reportClassImportAliasConflicts($diagnostics, $request, $build, $scopeLocator);
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
            caseSensitive: false,
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
        $scopeLocator = MemberGraphSymbolScopeLocator::fromBuild($build);
        $scope = $scopeLocator->functionNamespaceScope($this->namespaceName($newName));

        $this->reportShortNameConflict(
            diagnostics: $diagnostics,
            facts: $scope->functionDeclarations(),
            oldName: $this->shortName($request->oldName()),
            newName: $this->shortName($newName),
            policy: $request->conflictPolicy,
            message: sprintf('The function FQCN "%s" already exists.', $newName),
            caseSensitive: false,
        );
        $this->reportFunctionImportAliasConflicts($diagnostics, $request, $build, $scopeLocator);
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
            caseSensitive: true,
        );
        $this->reportMatchNameConflict(
            diagnostics: $diagnostics,
            matches: $scope->localVariables(),
            oldName: $request->oldName(),
            newName: $request->newName(),
            policy: $request->conflictPolicy,
            message: sprintf('The local variable name "$%s" already exists in the declaring body.', $request->newName()),
            caseSensitive: true,
        );
    }

    /**
     * Reports one fact-name conflict when present.
     *
     * @param RenameDiagnosticCollection           $diagnostics   the diagnostics to update
     * @param MemberGraphSymbolScopeFactCollection $facts         the scope facts to inspect
     * @param string                               $oldName       the old symbol name
     * @param string                               $newName       the new symbol name
     * @param RenameConflictPolicy                 $policy        the conflict policy
     * @param string                               $message       the diagnostic message
     * @param bool                                 $caseSensitive whether names must be compared case-sensitively
     */
    private function reportNameConflict(
        RenameDiagnosticCollection $diagnostics,
        MemberGraphSymbolScopeFactCollection $facts,
        string $oldName,
        string $newName,
        RenameConflictPolicy $policy,
        string $message,
        bool $caseSensitive,
    ): void {
        if (!$this->hasFactName($facts, $oldName, $newName, $caseSensitive)) {
            return;
        }

        $diagnostics->add(new RenameDiagnostic($this->severity($policy), $message));
    }

    /**
     * Reports one fact short-name conflict when present.
     *
     * @param RenameDiagnosticCollection           $diagnostics   the diagnostics to update
     * @param MemberGraphSymbolScopeFactCollection $facts         the scope facts to inspect
     * @param string                               $oldName       the old symbol name
     * @param string                               $newName       the new symbol name
     * @param RenameConflictPolicy                 $policy        the conflict policy
     * @param string                               $message       the diagnostic message
     * @param bool                                 $caseSensitive whether names must be compared case-sensitively
     */
    private function reportShortNameConflict(
        RenameDiagnosticCollection $diagnostics,
        MemberGraphSymbolScopeFactCollection $facts,
        string $oldName,
        string $newName,
        RenameConflictPolicy $policy,
        string $message,
        bool $caseSensitive,
    ): void {
        if (!$this->hasFactShortName($facts, $oldName, $newName, $caseSensitive)) {
            return;
        }

        $diagnostics->add(new RenameDiagnostic($this->severity($policy), $message));
    }

    /**
     * Reports one source-node match conflict when present.
     *
     * @param RenameDiagnosticCollection              $diagnostics   the diagnostics to update
     * @param VirtualPhpSourceFileNodeMatchCollection $matches       the source-node matches to inspect
     * @param string                                  $oldName       the old symbol name
     * @param string                                  $newName       the new symbol name
     * @param RenameConflictPolicy                    $policy        the conflict policy
     * @param string                                  $message       the diagnostic message
     * @param bool                                    $caseSensitive whether names must be compared case-sensitively
     */
    private function reportMatchNameConflict(
        RenameDiagnosticCollection $diagnostics,
        VirtualPhpSourceFileNodeMatchCollection $matches,
        string $oldName,
        string $newName,
        RenameConflictPolicy $policy,
        string $message,
        bool $caseSensitive,
    ): void {
        if ($this->sameName($oldName, $newName, $caseSensitive) || !$this->hasMatchName($matches, $newName, $caseSensitive)) {
            return;
        }

        $diagnostics->add(new RenameDiagnostic($this->severity($policy), $message));
    }

    /**
     * Reports class-like import alias conflicts for one class FQCN rename.
     *
     * @param RenameDiagnosticCollection    $diagnostics  the diagnostics to update
     * @param ClassFqcnRenameRequest        $request      the rename request
     * @param MemberDependencyGraphBuild    $build        the member graph build
     * @param MemberGraphSymbolScopeLocator $scopeLocator the symbol scope locator
     */
    private function reportClassImportAliasConflicts(
        RenameDiagnosticCollection $diagnostics,
        ClassFqcnRenameRequest $request,
        MemberDependencyGraphBuild $build,
        MemberGraphSymbolScopeLocator $scopeLocator,
    ): void {
        $newName = $request->newName();
        $newShortName = $this->shortName($newName);
        $newNamespaceName = $this->namespaceName($newName);
        $visitedVirtualFiles = [];
        $matches = MemberGraphSourceNodeLocator::fromBuild($build)->owner($request->className)->ownerUsages();

        foreach ($matches as $match) {
            if (true === ($visitedVirtualFiles[$match->virtualFile->virtualFilePath] ?? false)) {
                continue;
            }

            $visitedVirtualFiles[$match->virtualFile->virtualFilePath] = true;

            if ($this->nodeNamespaceName($match->node) === $newNamespaceName) {
                continue;
            }

            if (!$this->hasImportAliasConflict($scopeLocator->fileImportScope($match->virtualFile)->classLikeImports(), $request->oldName(), $newName, $newShortName, false)) {
                continue;
            }

            $diagnostics->add(new RenameDiagnostic(
                severity: $this->severity($request->conflictPolicy),
                message: sprintf('The class-like import alias "%s" already exists in a usage file.', $newShortName),
            ));
        }
    }

    /**
     * Reports function import alias conflicts for one function FQCN rename.
     *
     * @param RenameDiagnosticCollection    $diagnostics  the diagnostics to update
     * @param FunctionFqcnRenameRequest     $request      the rename request
     * @param MemberDependencyGraphBuild    $build        the member graph build
     * @param MemberGraphSymbolScopeLocator $scopeLocator the symbol scope locator
     */
    private function reportFunctionImportAliasConflicts(
        RenameDiagnosticCollection $diagnostics,
        FunctionFqcnRenameRequest $request,
        MemberDependencyGraphBuild $build,
        MemberGraphSymbolScopeLocator $scopeLocator,
    ): void {
        $newName = $request->newName();
        $newShortName = $this->shortName($newName);
        $newNamespaceName = $this->namespaceName($newName);
        $visitedVirtualFiles = [];
        $matches = MemberGraphSourceNodeLocator::fromBuild($build)->function($request->functionName)->memberUsages();

        foreach ($matches as $match) {
            if (true === ($visitedVirtualFiles[$match->virtualFile->virtualFilePath] ?? false)) {
                continue;
            }

            $visitedVirtualFiles[$match->virtualFile->virtualFilePath] = true;

            if ($this->nodeNamespaceName($match->node) === $newNamespaceName) {
                continue;
            }

            if (!$this->hasImportAliasConflict($scopeLocator->fileImportScope($match->virtualFile)->functionImports(), $request->oldName(), $newName, $newShortName, false)) {
                continue;
            }

            $diagnostics->add(new RenameDiagnostic(
                severity: $this->severity($request->conflictPolicy),
                message: sprintf('The function import alias "%s" already exists in a usage file.', $newShortName),
            ));
        }
    }

    /**
     * Indicates whether import facts contain an alias collision.
     *
     * @param MemberGraphSymbolScopeFactCollection $facts         the import facts
     * @param string                               $oldName       the old FQCN
     * @param string                               $newName       the new FQCN
     * @param string                               $newShortName  the new short name
     * @param bool                                 $caseSensitive whether aliases must be compared case-sensitively
     */
    private function hasImportAliasConflict(
        MemberGraphSymbolScopeFactCollection $facts,
        string $oldName,
        string $newName,
        string $newShortName,
        bool $caseSensitive,
    ): bool {
        foreach ($facts as $fact) {
            if (null === $fact->alias || !$this->sameName($fact->alias, $newShortName, $caseSensitive)) {
                continue;
            }

            if (null !== $fact->fqcn && ($this->sameFqcn($fact->fqcn, $oldName, $caseSensitive) || $this->sameFqcn($fact->fqcn, $newName, $caseSensitive))) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * Indicates whether facts contain the requested local name.
     *
     * @param MemberGraphSymbolScopeFactCollection $facts         the scope facts to inspect
     * @param string                               $oldName       the old symbol name
     * @param string                               $newName       the new symbol name
     * @param bool                                 $caseSensitive whether names must be compared case-sensitively
     */
    private function hasFactName(MemberGraphSymbolScopeFactCollection $facts, string $oldName, string $newName, bool $caseSensitive): bool
    {
        if ($this->sameName($oldName, $newName, $caseSensitive)) {
            return false;
        }

        foreach ($facts as $fact) {
            if ($this->sameName($fact->name, $newName, $caseSensitive)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Indicates whether facts contain the requested short name.
     *
     * @param MemberGraphSymbolScopeFactCollection $facts         the scope facts to inspect
     * @param string                               $oldName       the old symbol short name
     * @param string                               $newName       the new symbol short name
     * @param bool                                 $caseSensitive whether names must be compared case-sensitively
     */
    private function hasFactShortName(MemberGraphSymbolScopeFactCollection $facts, string $oldName, string $newName, bool $caseSensitive): bool
    {
        if ($this->sameName($oldName, $newName, $caseSensitive)) {
            return false;
        }

        foreach ($facts as $fact) {
            if ($this->sameName($this->factShortName($fact), $newName, $caseSensitive)) {
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
     * Indicates whether source-node matches contain the requested local name.
     *
     * @param VirtualPhpSourceFileNodeMatchCollection $matches       the source-node matches to inspect
     * @param string                                  $newName       the new symbol name
     * @param bool                                    $caseSensitive whether names must be compared case-sensitively
     */
    private function hasMatchName(VirtualPhpSourceFileNodeMatchCollection $matches, string $newName, bool $caseSensitive): bool
    {
        if ($caseSensitive) {
            return $matches->hasName($newName);
        }

        foreach ($matches as $match) {
            $nodeName = $this->nodeName($match->node);

            if (null !== $nodeName && $this->sameName($nodeName, $newName, false)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the variable-like name represented by one supported source-node match.
     *
     * @param Node $node the source-node match node
     */
    private function nodeName(Node $node): ?string
    {
        if ($node instanceof Param && $node->var instanceof Variable && is_string($node->var->name)) {
            return $node->var->name;
        }

        if ($node instanceof Variable && is_string($node->name)) {
            return $node->name;
        }

        return null;
    }

    /**
     * Returns the nearest namespace name for one matched node.
     *
     * @param Node $node the matched node
     */
    private function nodeNamespaceName(Node $node): string
    {
        $parent = $node->getAttribute('parent');

        while ($parent instanceof Node) {
            if ($parent instanceof Namespace_) {
                return $parent->name?->toString() ?? '';
            }

            $parent = $parent->getAttribute('parent');
        }

        return '';
    }

    /**
     * Indicates whether two names are equal under the requested comparison mode.
     *
     * @param string $left          the left name
     * @param string $right         the right name
     * @param bool   $caseSensitive whether names must be compared case-sensitively
     */
    private function sameName(string $left, string $right, bool $caseSensitive): bool
    {
        if ($caseSensitive) {
            return $left === $right;
        }

        return strtolower($left) === strtolower($right);
    }

    /**
     * Indicates whether two FQCN-like names are equal.
     *
     * @param string $left          the left FQCN
     * @param string $right         the right FQCN
     * @param bool   $caseSensitive whether names must be compared case-sensitively
     */
    private function sameFqcn(string $left, string $right, bool $caseSensitive): bool
    {
        return $this->sameName(ltrim($left, '\\'), ltrim($right, '\\'), $caseSensitive);
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
