<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Application;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphFactory;
use PhpNoobs\MemberGraph\Application\Build\Projection\MemberGraphBuildOverlay;
use PhpNoobs\MemberGraph\Application\Build\Projection\MemberGraphProjectedBuildFactory;
use PhpNoobs\PhpRename\Application\Contract\ClassConstantRenamePlannerInterface;
use PhpNoobs\PhpRename\Application\Contract\ClassFqcnRenamePlannerInterface;
use PhpNoobs\PhpRename\Application\Contract\ClassRenamePlannerInterface;
use PhpNoobs\PhpRename\Application\Contract\ConstantFqcnRenamePlannerInterface;
use PhpNoobs\PhpRename\Application\Contract\ConstantRenamePlannerInterface;
use PhpNoobs\PhpRename\Application\Contract\EnumCaseRenamePlannerInterface;
use PhpNoobs\PhpRename\Application\Contract\FunctionFqcnRenamePlannerInterface;
use PhpNoobs\PhpRename\Application\Contract\FunctionRenamePlannerInterface;
use PhpNoobs\PhpRename\Application\Contract\MethodRenamePlannerInterface;
use PhpNoobs\PhpRename\Application\Contract\ParameterRenamePlannerInterface;
use PhpNoobs\PhpRename\Application\Contract\PropertyRenamePlannerInterface;
use PhpNoobs\PhpRename\Application\Contract\RenamePlanApplierInterface;
use PhpNoobs\PhpRename\Domain\Rename\Conflict\RenameConflictPolicy;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnostic;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticCollection;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticSeverity;
use PhpNoobs\PhpRename\Domain\Rename\Operation\RenameOperation;
use PhpNoobs\PhpRename\Domain\Rename\Plan\RenamePlan;
use PhpNoobs\PhpRename\Domain\Rename\Plan\RenameResult;
use PhpNoobs\PhpRename\Domain\Rename\Request\ClassConstantRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\Request\ClassFqcnRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\Request\ClassRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\Request\ConstantFqcnRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\Request\ConstantRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\Request\EnumCaseRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\Request\FunctionFqcnRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\Request\FunctionRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\Request\MethodRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\Request\ParameterRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\Request\PropertyRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\Request\RenameRequestInterface;
use PhpNoobs\PhpRename\Domain\Rename\Transaction\RenameTransactionResult;
use PhpNoobs\PhpRename\Domain\Rename\Transaction\RenameTransactionStatus;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Transaction\VirtualPhpSourceFileSnapshot;

/**
 * Applies multiple rename actions against a refreshed in-memory member graph build.
 */
final class PhpRenameTransaction
{
    /**
     * @var list<RenameResult>
     */
    private array $actionResults = [];

    /**
     * @var array<string, VirtualPhpSourceFileSnapshot>
     */
    private array $snapshots = [];

    private RenameTransactionStatus $status = RenameTransactionStatus::ACTIVE;
    private RenameDiagnosticCollection $diagnostics;
    private MemberDependencyGraphBuild $baseBuild;
    private MemberGraphBuildOverlay $overlay;

    /**
     * Constructor.
     *
     * @param MemberDependencyGraphBuild          $currentBuild               the current transaction build
     * @param MethodRenamePlannerInterface        $methodRenamePlanner        the method rename planner
     * @param PropertyRenamePlannerInterface      $propertyRenamePlanner      the property rename planner
     * @param ClassConstantRenamePlannerInterface $classConstantRenamePlanner the class-constant rename planner
     * @param EnumCaseRenamePlannerInterface      $enumCaseRenamePlanner      the enum-case rename planner
     * @param ClassRenamePlannerInterface         $classRenamePlanner         the class rename planner
     * @param ClassFqcnRenamePlannerInterface     $classFqcnRenamePlanner     the class FQCN rename planner
     * @param FunctionRenamePlannerInterface      $functionRenamePlanner      the function rename planner
     * @param FunctionFqcnRenamePlannerInterface  $functionFqcnRenamePlanner  the function FQCN rename planner
     * @param ConstantRenamePlannerInterface      $constantRenamePlanner      the constant rename planner
     * @param ConstantFqcnRenamePlannerInterface  $constantFqcnRenamePlanner  the constant FQCN rename planner
     * @param ParameterRenamePlannerInterface     $parameterRenamePlanner     the parameter rename planner
     * @param RenamePlanApplierInterface          $renamePlanApplier          the rename plan applier
     */
    public function __construct(
        private MemberDependencyGraphBuild $currentBuild,
        private readonly MethodRenamePlannerInterface $methodRenamePlanner,
        private readonly PropertyRenamePlannerInterface $propertyRenamePlanner,
        private readonly ClassConstantRenamePlannerInterface $classConstantRenamePlanner,
        private readonly EnumCaseRenamePlannerInterface $enumCaseRenamePlanner,
        private readonly ClassRenamePlannerInterface $classRenamePlanner,
        private readonly ClassFqcnRenamePlannerInterface $classFqcnRenamePlanner,
        private readonly FunctionRenamePlannerInterface $functionRenamePlanner,
        private readonly FunctionFqcnRenamePlannerInterface $functionFqcnRenamePlanner,
        private readonly ConstantRenamePlannerInterface $constantRenamePlanner,
        private readonly ConstantFqcnRenamePlannerInterface $constantFqcnRenamePlanner,
        private readonly ParameterRenamePlannerInterface $parameterRenamePlanner,
        private readonly RenamePlanApplierInterface $renamePlanApplier,
    ) {
        $this->diagnostics = RenameDiagnosticCollection::empty();
        $this->baseBuild = $currentBuild;
        $this->overlay = MemberGraphBuildOverlay::empty();
    }

    /**
     * Plans and applies a method rename within the transaction.
     *
     * @param string               $className      the class name that anchors the method rename
     * @param string               $methodName     the current method name
     * @param string               $newMethodName  the replacement method name
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     *
     * @throws \LogicException when the transaction is no longer active
     */
    public function renameMethod(
        string $className,
        string $methodName,
        string $newMethodName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->execute($this->renamer()->planMethodRename($className, $methodName, $newMethodName, $conflictPolicy));
    }

    /**
     * Plans and applies a property rename within the transaction.
     *
     * @param string               $className       the class name that anchors the property rename
     * @param string               $propertyName    the current property name
     * @param string               $newPropertyName the replacement property name
     * @param RenameConflictPolicy $conflictPolicy  the rename conflict policy
     *
     * @throws \LogicException when the transaction is no longer active
     */
    public function renameProperty(
        string $className,
        string $propertyName,
        string $newPropertyName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->execute($this->renamer()->planPropertyRename($className, $propertyName, $newPropertyName, $conflictPolicy));
    }

    /**
     * Plans and applies a class-constant rename within the transaction.
     *
     * @param string               $className       the class name that anchors the class-constant rename
     * @param string               $constantName    the current class-constant name
     * @param string               $newConstantName the replacement class-constant name
     * @param RenameConflictPolicy $conflictPolicy  the rename conflict policy
     *
     * @throws \LogicException when the transaction is no longer active
     */
    public function renameClassConstant(
        string $className,
        string $constantName,
        string $newConstantName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->execute($this->renamer()->planClassConstantRename($className, $constantName, $newConstantName, $conflictPolicy));
    }

    /**
     * Plans and applies an enum-case rename within the transaction.
     *
     * @param string               $enumName       the enum name that anchors the enum-case rename
     * @param string               $caseName       the current enum-case name
     * @param string               $newCaseName    the replacement enum-case name
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     *
     * @throws \LogicException when the transaction is no longer active
     */
    public function renameEnumCase(
        string $enumName,
        string $caseName,
        string $newCaseName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->execute($this->renamer()->planEnumCaseRename($enumName, $caseName, $newCaseName, $conflictPolicy));
    }

    /**
     * Plans and applies a class-like owner rename within the transaction.
     *
     * @param string               $className      the current fully-qualified class-like owner name
     * @param string               $newClassName   the replacement short class-like owner name
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     *
     * @throws \LogicException when the transaction is no longer active
     */
    public function renameClass(
        string $className,
        string $newClassName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->execute($this->renamer()->planClassRename($className, $newClassName, $conflictPolicy));
    }

    /**
     * Plans and applies a class-like owner FQCN rename within the transaction.
     *
     * @param string               $className      the current fully-qualified class-like owner name
     * @param string               $newClassName   the replacement fully-qualified class-like owner name
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     *
     * @throws \LogicException when the transaction is no longer active
     */
    public function renameClassFqcn(
        string $className,
        string $newClassName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->execute($this->renamer()->planClassFqcnRename($className, $newClassName, $conflictPolicy));
    }

    /**
     * Plans and applies a function rename within the transaction.
     *
     * @param string               $functionName    the current fully-qualified function name
     * @param string               $newFunctionName the replacement short function name
     * @param RenameConflictPolicy $conflictPolicy  the rename conflict policy
     *
     * @throws \LogicException when the transaction is no longer active
     */
    public function renameFunction(
        string $functionName,
        string $newFunctionName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->execute($this->renamer()->planFunctionRename($functionName, $newFunctionName, $conflictPolicy));
    }

    /**
     * Plans and applies a function FQCN rename within the transaction.
     *
     * @param string               $functionName    the current fully-qualified function name
     * @param string               $newFunctionName the replacement fully-qualified function name
     * @param RenameConflictPolicy $conflictPolicy  the rename conflict policy
     *
     * @throws \LogicException when the transaction is no longer active
     */
    public function renameFunctionFqcn(
        string $functionName,
        string $newFunctionName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->execute($this->renamer()->planFunctionFqcnRename($functionName, $newFunctionName, $conflictPolicy));
    }

    /**
     * Plans and applies a namespace-level constant rename within the transaction.
     *
     * @param string               $constantName    the current fully-qualified constant name
     * @param string               $newConstantName the replacement short constant name
     * @param RenameConflictPolicy $conflictPolicy  the rename conflict policy
     *
     * @throws \LogicException when the transaction is no longer active
     */
    public function renameConstant(
        string $constantName,
        string $newConstantName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->execute($this->renamer()->planConstantRename($constantName, $newConstantName, $conflictPolicy));
    }

    /**
     * Plans and applies a namespace-level constant FQCN rename within the transaction.
     *
     * @param string               $constantName    the current fully-qualified constant name
     * @param string               $newConstantName the replacement fully-qualified constant name
     * @param RenameConflictPolicy $conflictPolicy  the rename conflict policy
     *
     * @throws \LogicException when the transaction is no longer active
     */
    public function renameConstantFqcn(
        string $constantName,
        string $newConstantName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->execute($this->renamer()->planConstantFqcnRename($constantName, $newConstantName, $conflictPolicy));
    }

    /**
     * Plans and applies a method parameter rename within the transaction.
     *
     * @param string               $className        the method owner FQCN
     * @param string               $methodName       the method name
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based declaration index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     *
     * @throws \LogicException when the transaction is no longer active
     */
    public function renameMethodParameter(
        string $className,
        string $methodName,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->execute($this->renamer()->planMethodParameterRename($className, $methodName, $parameterName, $newParameterName, $parameterIndex, $conflictPolicy));
    }

    /**
     * Plans and applies a function parameter rename within the transaction.
     *
     * @param string               $functionName     the fully-qualified function name
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based declaration index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     *
     * @throws \LogicException when the transaction is no longer active
     */
    public function renameFunctionParameter(
        string $functionName,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->execute($this->renamer()->planFunctionParameterRename($functionName, $parameterName, $newParameterName, $parameterIndex, $conflictPolicy));
    }

    /**
     * Commits the transaction and returns its aggregate result.
     */
    public function commit(): RenameTransactionResult
    {
        if (RenameTransactionStatus::ACTIVE === $this->status) {
            $this->status = RenameTransactionStatus::COMMITTED;
        }

        return $this->result($this->status);
    }

    /**
     * Commits the transaction and writes every updated source file.
     *
     * @throws \RuntimeException when source writing fails
     */
    public function commitAndSave(): RenameTransactionResult
    {
        $result = $this->commit();

        if (RenameTransactionStatus::COMMITTED === $result->status) {
            $result->finalBuild->sourceRegistry()->save();
        }

        return $result;
    }

    /**
     * Commits the transaction and writes one updated physical source file.
     *
     * @param string $filePath the physical source file path to save
     *
     * @throws \RuntimeException when the source file is unknown or source writing fails
     */
    public function commitAndSaveSourceFile(string $filePath): RenameTransactionResult
    {
        $result = $this->commit();

        if (RenameTransactionStatus::COMMITTED === $result->status) {
            $result->finalBuild->sourceRegistry()->saveSourceFile($filePath);
        }

        return $result;
    }

    /**
     * Rolls back all virtual files touched by successful transaction actions.
     */
    public function rollback(): RenameTransactionResult
    {
        foreach ($this->currentBuild->virtualFiles as $virtualFile) {
            $snapshot = $this->snapshots[$virtualFile->virtualFilePath] ?? null;

            if (null === $snapshot) {
                continue;
            }

            $snapshot->restore($virtualFile);
        }

        $this->overlay = MemberGraphBuildOverlay::empty();
        $this->currentBuild = MemberDependencyGraphFactory::fromVirtualFiles($this->baseBuild->virtualFiles);
        $this->baseBuild = $this->currentBuild;
        $this->status = RenameTransactionStatus::ROLLED_BACK;

        return $this->result($this->status);
    }

    /**
     * Returns the current transaction status.
     */
    public function status(): RenameTransactionStatus
    {
        return $this->status;
    }

    /**
     * Executes one planned rename action.
     *
     * @param RenamePlan $plan the plan to execute
     *
     * @throws \LogicException when the transaction is no longer active
     */
    private function execute(RenamePlan $plan): RenameResult
    {
        $this->guardActive();
        $this->mergeDiagnostics($plan->diagnostics);

        if ($plan->diagnostics->hasErrors()) {
            $this->status = RenameTransactionStatus::FAILED;
            $result = new RenameResult($plan, $this->currentBuild->virtualFiles, RenameDiagnosticCollection::empty());
            $this->actionResults[] = $result;

            return $result;
        }

        $this->snapshotPlanVirtualFiles($plan);

        $result = $this->renamePlanApplier->apply($plan, $this->currentBuild);
        $this->mergeDiagnostics($result->diagnostics);
        $this->actionResults[] = $result;

        if ($result->diagnostics->hasErrors()) {
            $this->status = RenameTransactionStatus::FAILED;

            return $result;
        }

        $this->refreshCurrentBuild($plan);

        return $result;
    }

    /**
     * Refreshes the current transaction build after one successful action.
     *
     * @param RenamePlan $plan the successfully applied plan
     */
    private function refreshCurrentBuild(RenamePlan $plan): void
    {
        if (0 === count($plan->operations)) {
            return;
        }

        if (!$this->recordOverlayUpdate($plan->request)) {
            $this->currentBuild = MemberDependencyGraphFactory::fromVirtualFiles($this->currentBuild->virtualFiles);
            $this->baseBuild = $this->currentBuild;
            $this->overlay = MemberGraphBuildOverlay::empty();

            return;
        }

        $this->currentBuild = MemberGraphProjectedBuildFactory::fromBuild(
            build: $this->baseBuild,
            overlay: $this->overlay,
        );
    }

    /**
     * Records one supported semantic identity update in the transaction overlay.
     *
     * @param RenameRequestInterface $request the applied rename request
     */
    private function recordOverlayUpdate(RenameRequestInterface $request): bool
    {
        if ($request instanceof ClassFqcnRenameRequest) {
            $this->overlay = $this->overlay->withOwnerUpdate($request->oldName(), $request->newName());

            return true;
        }

        if ($request instanceof ClassRenameRequest) {
            $this->overlay = $this->overlay->withOwnerUpdate(
                owner: $request->className,
                newOwner: $this->namespacedName($request->className, $request->newClassName),
            );

            return true;
        }

        if ($request instanceof MethodRenameRequest) {
            $this->overlay = $this->overlay->withMethodUpdate($request->className, $request->methodName, $request->newMethodName);

            return true;
        }

        if ($request instanceof PropertyRenameRequest) {
            $this->overlay = $this->overlay->withPropertyUpdate($request->className, $request->propertyName, $request->newPropertyName);

            return true;
        }

        if ($request instanceof ClassConstantRenameRequest) {
            $this->overlay = $this->overlay->withClassConstantUpdate($request->className, $request->constantName, $request->newConstantName);

            return true;
        }

        if ($request instanceof EnumCaseRenameRequest) {
            $this->overlay = $this->overlay->withEnumCaseUpdate($request->enumName, $request->caseName, $request->newCaseName);

            return true;
        }

        if ($request instanceof FunctionFqcnRenameRequest) {
            $this->overlay = $this->overlay->withFunctionUpdate($request->oldName(), $request->newName());

            return true;
        }

        if ($request instanceof FunctionRenameRequest) {
            $this->overlay = $this->overlay->withFunctionUpdate(
                name: $request->functionName,
                newName: $this->namespacedName($request->functionName, $request->newFunctionName),
            );

            return true;
        }

        if ($request instanceof ConstantFqcnRenameRequest) {
            $this->overlay = $this->overlay->withNamespaceConstantUpdate($request->oldName(), $request->newName());

            return true;
        }

        if ($request instanceof ConstantRenameRequest) {
            $this->overlay = $this->overlay->withNamespaceConstantUpdate(
                name: $request->constantName,
                newName: $this->namespacedName($request->constantName, $request->newConstantName),
            );

            return true;
        }

        if ($request instanceof ParameterRenameRequest) {
            $this->overlay = $this->overlay->withParameterUpdate(
                owner: $request->owner,
                functionLikeName: $request->functionLikeName,
                parameterName: $request->parameterName,
                newParameterName: $request->newParameterName,
                parameterIndex: $request->parameterIndex,
            );

            return true;
        }

        return false;
    }

    /**
     * Replaces the short name part of one fully-qualified symbol name.
     *
     * @param string $currentName  the current fully-qualified symbol name
     * @param string $newShortName the replacement short name
     */
    private function namespacedName(string $currentName, string $newShortName): string
    {
        $parts = explode('\\', ltrim($currentName, '\\'));
        array_pop($parts);

        if ([] === $parts) {
            return $newShortName;
        }

        return implode('\\', $parts).'\\'.$newShortName;
    }

    /**
     * Stores snapshots for virtual files touched by one plan.
     *
     * @param RenamePlan $plan the plan to inspect
     */
    private function snapshotPlanVirtualFiles(RenamePlan $plan): void
    {
        foreach ($plan->operations as $operation) {
            $this->snapshotVirtualFile($operation);
        }
    }

    /**
     * Stores a snapshot for one operation virtual file.
     *
     * @param RenameOperation $operation the operation to inspect
     */
    private function snapshotVirtualFile(RenameOperation $operation): void
    {
        $virtualFile = $operation->file;

        if (isset($this->snapshots[$virtualFile->virtualFilePath])) {
            return;
        }

        $this->snapshots[$virtualFile->virtualFilePath] = VirtualPhpSourceFileSnapshot::fromVirtualFile($virtualFile);
    }

    /**
     * Adds diagnostics to the aggregate collection.
     *
     * @param RenameDiagnosticCollection $diagnostics the diagnostics to merge
     */
    private function mergeDiagnostics(RenameDiagnosticCollection $diagnostics): void
    {
        foreach ($diagnostics as $diagnostic) {
            $this->diagnostics->add($diagnostic);
        }
    }

    /**
     * Creates a transaction result for the current state.
     *
     * @param RenameTransactionStatus $status the status to expose
     */
    private function result(RenameTransactionStatus $status): RenameTransactionResult
    {
        return new RenameTransactionResult(
            status: $status,
            actionResults: $this->actionResults,
            finalBuild: $this->currentBuild,
            virtualFiles: $this->currentBuild->virtualFiles,
            diagnostics: $this->diagnostics,
        );
    }

    /**
     * Creates a facade bound to the current transaction build and shared services.
     */
    private function renamer(): PhpRename
    {
        return PhpRename::fromBuild(
            build: $this->currentBuild,
            methodRenamePlanner: $this->methodRenamePlanner,
            renamePlanApplier: $this->renamePlanApplier,
            propertyRenamePlanner: $this->propertyRenamePlanner,
            classConstantRenamePlanner: $this->classConstantRenamePlanner,
            classRenamePlanner: $this->classRenamePlanner,
            classFqcnRenamePlanner: $this->classFqcnRenamePlanner,
            functionRenamePlanner: $this->functionRenamePlanner,
            functionFqcnRenamePlanner: $this->functionFqcnRenamePlanner,
            parameterRenamePlanner: $this->parameterRenamePlanner,
            enumCaseRenamePlanner: $this->enumCaseRenamePlanner,
            constantRenamePlanner: $this->constantRenamePlanner,
            constantFqcnRenamePlanner: $this->constantFqcnRenamePlanner,
        );
    }

    /**
     * Ensures the transaction is active before accepting another rename action.
     *
     * @throws \LogicException when the transaction is no longer active
     */
    private function guardActive(): void
    {
        if (RenameTransactionStatus::ACTIVE === $this->status) {
            return;
        }

        $this->diagnostics->add(new RenameDiagnostic(
            severity: RenameDiagnosticSeverity::ERROR,
            message: 'Cannot execute a rename action on a non-active transaction.',
        ));

        throw new \LogicException('Cannot execute a rename action on a non-active transaction.');
    }
}
