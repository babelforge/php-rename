<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Application;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphFactory;
use BabelForge\PhpRename\Application\Contract\ClassConstantRenamePlannerInterface;
use BabelForge\PhpRename\Application\Contract\ClassFqcnRenamePlannerInterface;
use BabelForge\PhpRename\Application\Contract\ClassRenamePlannerInterface;
use BabelForge\PhpRename\Application\Contract\ConstantFqcnRenamePlannerInterface;
use BabelForge\PhpRename\Application\Contract\ConstantRenamePlannerInterface;
use BabelForge\PhpRename\Application\Contract\EnumCaseRenamePlannerInterface;
use BabelForge\PhpRename\Application\Contract\FunctionFqcnRenamePlannerInterface;
use BabelForge\PhpRename\Application\Contract\FunctionRenamePlannerInterface;
use BabelForge\PhpRename\Application\Contract\MethodRenamePlannerInterface;
use BabelForge\PhpRename\Application\Contract\NestedCallableLocalVariableRenamePlannerInterface;
use BabelForge\PhpRename\Application\Contract\NestedCallableRenamePlannerInterface;
use BabelForge\PhpRename\Application\Contract\ParameterRenamePlannerInterface;
use BabelForge\PhpRename\Application\Contract\PropertyRenamePlannerInterface;
use BabelForge\PhpRename\Application\Contract\RenamePlanApplierInterface;
use BabelForge\PhpRename\Domain\Rename\Conflict\RenameConflictPolicy;
use BabelForge\PhpRename\Domain\Rename\Plan\RenamePlan;
use BabelForge\PhpRename\Domain\Rename\Plan\RenameResult;
use BabelForge\PhpRename\Domain\Rename\Request\ClassConstantRenameRequest;
use BabelForge\PhpRename\Domain\Rename\Request\ClassFqcnRenameRequest;
use BabelForge\PhpRename\Domain\Rename\Request\ClassRenameRequest;
use BabelForge\PhpRename\Domain\Rename\Request\ConstantFqcnRenameRequest;
use BabelForge\PhpRename\Domain\Rename\Request\ConstantRenameRequest;
use BabelForge\PhpRename\Domain\Rename\Request\EnumCaseRenameRequest;
use BabelForge\PhpRename\Domain\Rename\Request\FunctionFqcnRenameRequest;
use BabelForge\PhpRename\Domain\Rename\Request\FunctionRenameRequest;
use BabelForge\PhpRename\Domain\Rename\Request\MethodRenameRequest;
use BabelForge\PhpRename\Domain\Rename\Request\ParameterRenameRequest;
use BabelForge\PhpRename\Domain\Rename\Request\PropertyRenameRequest;
use BabelForge\PhpRename\Domain\Rename\Step\RenameStepContext;
use BabelForge\PhpRename\Domain\Rename\Step\RenameStepResult;
use BabelForge\PhpRename\Infrastructure\MemberGraph\Planner\MemberGraphClassConstantRenamePlanner;
use BabelForge\PhpRename\Infrastructure\MemberGraph\Planner\MemberGraphClassFqcnRenamePlanner;
use BabelForge\PhpRename\Infrastructure\MemberGraph\Planner\MemberGraphClassRenamePlanner;
use BabelForge\PhpRename\Infrastructure\MemberGraph\Planner\MemberGraphConstantFqcnRenamePlanner;
use BabelForge\PhpRename\Infrastructure\MemberGraph\Planner\MemberGraphConstantRenamePlanner;
use BabelForge\PhpRename\Infrastructure\MemberGraph\Planner\MemberGraphEnumCaseRenamePlanner;
use BabelForge\PhpRename\Infrastructure\MemberGraph\Planner\MemberGraphFunctionFqcnRenamePlanner;
use BabelForge\PhpRename\Infrastructure\MemberGraph\Planner\MemberGraphFunctionRenamePlanner;
use BabelForge\PhpRename\Infrastructure\MemberGraph\Planner\MemberGraphMethodRenamePlanner;
use BabelForge\PhpRename\Infrastructure\MemberGraph\Planner\MemberGraphNestedCallableRenamePlanner;
use BabelForge\PhpRename\Infrastructure\MemberGraph\Planner\MemberGraphParameterRenamePlanner;
use BabelForge\PhpRename\Infrastructure\MemberGraph\Planner\MemberGraphPropertyRenamePlanner;
use BabelForge\PhpRename\Infrastructure\PhpParser\AstRenamePlanApplier;

/**
 * Public facade for planning and applying PHP symbol renames.
 */
final readonly class PhpRename
{
    use NestedCallableLocalVariablePhpRenameMethods;
    use NestedCallablePhpRenameMethods;

    /**
     * Constructor.
     *
     * @param MemberDependencyGraphBuild                        $build                                    the member graph build used by rename operations
     * @param MethodRenamePlannerInterface                      $methodRenamePlanner                      the method rename planner
     * @param PropertyRenamePlannerInterface                    $propertyRenamePlanner                    the property rename planner
     * @param ClassConstantRenamePlannerInterface               $classConstantRenamePlanner               the class-constant rename planner
     * @param EnumCaseRenamePlannerInterface                    $enumCaseRenamePlanner                    the enum-case rename planner
     * @param ClassRenamePlannerInterface                       $classRenamePlanner                       the class rename planner
     * @param ClassFqcnRenamePlannerInterface                   $classFqcnRenamePlanner                   the class FQCN rename planner
     * @param FunctionRenamePlannerInterface                    $functionRenamePlanner                    the function rename planner
     * @param FunctionFqcnRenamePlannerInterface                $functionFqcnRenamePlanner                the function FQCN rename planner
     * @param ConstantRenamePlannerInterface                    $constantRenamePlanner                    the constant rename planner
     * @param ConstantFqcnRenamePlannerInterface                $constantFqcnRenamePlanner                the constant FQCN rename planner
     * @param ParameterRenamePlannerInterface                   $parameterRenamePlanner                   the parameter rename planner
     * @param NestedCallableRenamePlannerInterface              $nestedCallableRenamePlanner              the nested callable rename planner
     * @param NestedCallableLocalVariableRenamePlannerInterface $nestedCallableLocalVariableRenamePlanner the nested callable local variable rename planner
     * @param RenamePlanApplierInterface                        $renamePlanApplier                        the rename plan applier
     */
    private function __construct(
        private MemberDependencyGraphBuild $build,
        private MethodRenamePlannerInterface $methodRenamePlanner,
        private PropertyRenamePlannerInterface $propertyRenamePlanner,
        private ClassConstantRenamePlannerInterface $classConstantRenamePlanner,
        private EnumCaseRenamePlannerInterface $enumCaseRenamePlanner,
        private ClassRenamePlannerInterface $classRenamePlanner,
        private ClassFqcnRenamePlannerInterface $classFqcnRenamePlanner,
        private FunctionRenamePlannerInterface $functionRenamePlanner,
        private FunctionFqcnRenamePlannerInterface $functionFqcnRenamePlanner,
        private ConstantRenamePlannerInterface $constantRenamePlanner,
        private ConstantFqcnRenamePlannerInterface $constantFqcnRenamePlanner,
        private ParameterRenamePlannerInterface $parameterRenamePlanner,
        private NestedCallableRenamePlannerInterface $nestedCallableRenamePlanner,
        private NestedCallableLocalVariableRenamePlannerInterface $nestedCallableLocalVariableRenamePlanner,
        private RenamePlanApplierInterface $renamePlanApplier,
    ) {
    }

    /**
     * Creates a renamer from project directories.
     *
     * @param list<string> $directories         the directories to scan
     * @param string       $cacheFilePath       the member graph cache file path
     * @param list<string> $excludedDirectories the directories to exclude from scanning
     * @param bool         $clearCache          whether the member graph cache must be cleared first
     */
    public static function fromDirectory(
        array $directories,
        string $cacheFilePath,
        array $excludedDirectories = [],
        bool $clearCache = false,
    ): self {
        return self::fromBuild(MemberDependencyGraphFactory::fromDirectory(
            directories: $directories,
            cacheFilePath: $cacheFilePath,
            excludedDirectories: $excludedDirectories,
            clearCache: $clearCache,
        ));
    }

    /**
     * Creates a renamer from an existing member graph build.
     *
     * @param MemberDependencyGraphBuild                             $build                                    the member graph build
     * @param MethodRenamePlannerInterface|null                      $methodRenamePlanner                      the optional method rename planner override
     * @param RenamePlanApplierInterface|null                        $renamePlanApplier                        the optional rename plan applier override
     * @param PropertyRenamePlannerInterface|null                    $propertyRenamePlanner                    the optional property rename planner override
     * @param ClassConstantRenamePlannerInterface|null               $classConstantRenamePlanner               the optional class-constant rename planner override
     * @param ClassRenamePlannerInterface|null                       $classRenamePlanner                       the optional class rename planner override
     * @param ClassFqcnRenamePlannerInterface|null                   $classFqcnRenamePlanner                   the optional class FQCN rename planner override
     * @param FunctionRenamePlannerInterface|null                    $functionRenamePlanner                    the optional function rename planner override
     * @param FunctionFqcnRenamePlannerInterface|null                $functionFqcnRenamePlanner                the optional function FQCN rename planner override
     * @param ParameterRenamePlannerInterface|null                   $parameterRenamePlanner                   the optional parameter rename planner override
     * @param EnumCaseRenamePlannerInterface|null                    $enumCaseRenamePlanner                    the optional enum-case rename planner override
     * @param ConstantRenamePlannerInterface|null                    $constantRenamePlanner                    the optional constant rename planner override
     * @param ConstantFqcnRenamePlannerInterface|null                $constantFqcnRenamePlanner                the optional constant FQCN rename planner override
     * @param NestedCallableRenamePlannerInterface|null              $nestedCallableRenamePlanner              the optional nested callable rename planner override
     * @param NestedCallableLocalVariableRenamePlannerInterface|null $nestedCallableLocalVariableRenamePlanner the optional nested callable local variable rename planner override
     */
    public static function fromBuild(
        MemberDependencyGraphBuild $build,
        ?MethodRenamePlannerInterface $methodRenamePlanner = null,
        ?RenamePlanApplierInterface $renamePlanApplier = null,
        ?PropertyRenamePlannerInterface $propertyRenamePlanner = null,
        ?ClassConstantRenamePlannerInterface $classConstantRenamePlanner = null,
        ?ClassRenamePlannerInterface $classRenamePlanner = null,
        ?ClassFqcnRenamePlannerInterface $classFqcnRenamePlanner = null,
        ?FunctionRenamePlannerInterface $functionRenamePlanner = null,
        ?FunctionFqcnRenamePlannerInterface $functionFqcnRenamePlanner = null,
        ?ParameterRenamePlannerInterface $parameterRenamePlanner = null,
        ?EnumCaseRenamePlannerInterface $enumCaseRenamePlanner = null,
        ?ConstantRenamePlannerInterface $constantRenamePlanner = null,
        ?ConstantFqcnRenamePlannerInterface $constantFqcnRenamePlanner = null,
        ?NestedCallableRenamePlannerInterface $nestedCallableRenamePlanner = null,
        ?NestedCallableLocalVariableRenamePlannerInterface $nestedCallableLocalVariableRenamePlanner = null,
    ): self {
        return new self(
            build: $build,
            methodRenamePlanner: $methodRenamePlanner ?? new MemberGraphMethodRenamePlanner(),
            propertyRenamePlanner: $propertyRenamePlanner ?? new MemberGraphPropertyRenamePlanner(),
            classConstantRenamePlanner: $classConstantRenamePlanner ?? new MemberGraphClassConstantRenamePlanner(),
            enumCaseRenamePlanner: $enumCaseRenamePlanner ?? new MemberGraphEnumCaseRenamePlanner(),
            classRenamePlanner: $classRenamePlanner ?? new MemberGraphClassRenamePlanner(),
            classFqcnRenamePlanner: $classFqcnRenamePlanner ?? new MemberGraphClassFqcnRenamePlanner(),
            functionRenamePlanner: $functionRenamePlanner ?? new MemberGraphFunctionRenamePlanner(),
            functionFqcnRenamePlanner: $functionFqcnRenamePlanner ?? new MemberGraphFunctionFqcnRenamePlanner(),
            constantRenamePlanner: $constantRenamePlanner ?? new MemberGraphConstantRenamePlanner(),
            constantFqcnRenamePlanner: $constantFqcnRenamePlanner ?? new MemberGraphConstantFqcnRenamePlanner(),
            parameterRenamePlanner: $parameterRenamePlanner ?? new MemberGraphParameterRenamePlanner(),
            nestedCallableRenamePlanner: $nestedCallableRenamePlanner ?? new MemberGraphNestedCallableRenamePlanner(),
            nestedCallableLocalVariableRenamePlanner: $nestedCallableLocalVariableRenamePlanner ?? new MemberGraphNestedCallableRenamePlanner(),
            renamePlanApplier: $renamePlanApplier ?? new AstRenamePlanApplier(),
        );
    }

    /**
     * Starts a rename transaction from the current member graph build.
     */
    public function beginTransaction(): PhpRenameTransaction
    {
        return new PhpRenameTransaction(
            currentBuild: $this->build,
            methodRenamePlanner: $this->methodRenamePlanner,
            propertyRenamePlanner: $this->propertyRenamePlanner,
            classConstantRenamePlanner: $this->classConstantRenamePlanner,
            enumCaseRenamePlanner: $this->enumCaseRenamePlanner,
            classRenamePlanner: $this->classRenamePlanner,
            classFqcnRenamePlanner: $this->classFqcnRenamePlanner,
            functionRenamePlanner: $this->functionRenamePlanner,
            functionFqcnRenamePlanner: $this->functionFqcnRenamePlanner,
            constantRenamePlanner: $this->constantRenamePlanner,
            constantFqcnRenamePlanner: $this->constantFqcnRenamePlanner,
            parameterRenamePlanner: $this->parameterRenamePlanner,
            nestedCallableRenamePlanner: $this->nestedCallableRenamePlanner,
            nestedCallableLocalVariableRenamePlanner: $this->nestedCallableLocalVariableRenamePlanner,
            renamePlanApplier: $this->renamePlanApplier,
        );
    }

    /**
     * Executes one preplanned orchestrable rename step.
     *
     * @param RenamePlan        $plan    the rename plan to execute
     * @param RenameStepContext $context the current rename step context
     */
    public function executeStep(RenamePlan $plan, RenameStepContext $context): RenameStepResult
    {
        return new RenameStepExecutor($this->renamePlanApplier)->execute($plan, $context);
    }

    /**
     * Executes one orchestrable method rename step.
     *
     * @param RenameStepContext    $context        the current rename step context
     * @param string               $className      the class name that anchors the method rename
     * @param string               $methodName     the current method name
     * @param string               $newMethodName  the replacement method name
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function executeStepMethodRename(
        RenameStepContext $context,
        string $className,
        string $methodName,
        string $newMethodName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameStepResult {
        return $this->executeStep($this->methodRenamePlanner->plan(
            request: new MethodRenameRequest($className, $methodName, $newMethodName, $conflictPolicy),
            build: $context->currentBuild,
        ), $context);
    }

    /**
     * Executes one orchestrable property rename step.
     *
     * @param RenameStepContext    $context         the current rename step context
     * @param string               $className       the class name that anchors the property rename
     * @param string               $propertyName    the current property name
     * @param string               $newPropertyName the replacement property name
     * @param RenameConflictPolicy $conflictPolicy  the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function executeStepPropertyRename(
        RenameStepContext $context,
        string $className,
        string $propertyName,
        string $newPropertyName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameStepResult {
        return $this->executeStep($this->propertyRenamePlanner->plan(
            request: new PropertyRenameRequest($className, $propertyName, $newPropertyName, $conflictPolicy),
            build: $context->currentBuild,
        ), $context);
    }

    /**
     * Executes one orchestrable class-constant rename step.
     *
     * @param RenameStepContext    $context         the current rename step context
     * @param string               $className       the class name that anchors the class-constant rename
     * @param string               $constantName    the current class-constant name
     * @param string               $newConstantName the replacement class-constant name
     * @param RenameConflictPolicy $conflictPolicy  the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function executeStepClassConstantRename(
        RenameStepContext $context,
        string $className,
        string $constantName,
        string $newConstantName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameStepResult {
        return $this->executeStep($this->classConstantRenamePlanner->plan(
            request: new ClassConstantRenameRequest($className, $constantName, $newConstantName, $conflictPolicy),
            build: $context->currentBuild,
        ), $context);
    }

    /**
     * Executes one orchestrable enum-case rename step.
     *
     * @param RenameStepContext    $context        the current rename step context
     * @param string               $enumName       the enum name that anchors the enum-case rename
     * @param string               $caseName       the current enum-case name
     * @param string               $newCaseName    the replacement enum-case name
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function executeStepEnumCaseRename(
        RenameStepContext $context,
        string $enumName,
        string $caseName,
        string $newCaseName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameStepResult {
        return $this->executeStep($this->enumCaseRenamePlanner->plan(
            request: new EnumCaseRenameRequest($enumName, $caseName, $newCaseName, $conflictPolicy),
            build: $context->currentBuild,
        ), $context);
    }

    /**
     * Executes one orchestrable class-like owner rename step.
     *
     * @param RenameStepContext    $context        the current rename step context
     * @param string               $className      the current fully-qualified class-like owner name
     * @param string               $newClassName   the replacement short class-like owner name
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function executeStepClassRename(
        RenameStepContext $context,
        string $className,
        string $newClassName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameStepResult {
        return $this->executeStep($this->classRenamePlanner->plan(
            request: new ClassRenameRequest($className, $newClassName, $conflictPolicy),
            build: $context->currentBuild,
        ), $context);
    }

    /**
     * Executes one orchestrable class-like owner FQCN rename step.
     *
     * @param RenameStepContext    $context        the current rename step context
     * @param string               $className      the current fully-qualified class-like owner name
     * @param string               $newClassName   the replacement fully-qualified class-like owner name
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function executeStepClassFqcnRename(
        RenameStepContext $context,
        string $className,
        string $newClassName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameStepResult {
        return $this->executeStep($this->classFqcnRenamePlanner->plan(
            request: new ClassFqcnRenameRequest($className, $newClassName, $conflictPolicy),
            build: $context->currentBuild,
        ), $context);
    }

    /**
     * Executes one orchestrable function rename step.
     *
     * @param RenameStepContext    $context         the current rename step context
     * @param string               $functionName    the current fully-qualified function name
     * @param string               $newFunctionName the replacement short function name
     * @param RenameConflictPolicy $conflictPolicy  the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function executeStepFunctionRename(
        RenameStepContext $context,
        string $functionName,
        string $newFunctionName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameStepResult {
        return $this->executeStep($this->functionRenamePlanner->plan(
            request: new FunctionRenameRequest($functionName, $newFunctionName, $conflictPolicy),
            build: $context->currentBuild,
        ), $context);
    }

    /**
     * Executes one orchestrable function FQCN rename step.
     *
     * @param RenameStepContext    $context         the current rename step context
     * @param string               $functionName    the current fully-qualified function name
     * @param string               $newFunctionName the replacement fully-qualified function name
     * @param RenameConflictPolicy $conflictPolicy  the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function executeStepFunctionFqcnRename(
        RenameStepContext $context,
        string $functionName,
        string $newFunctionName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameStepResult {
        return $this->executeStep($this->functionFqcnRenamePlanner->plan(
            request: new FunctionFqcnRenameRequest($functionName, $newFunctionName, $conflictPolicy),
            build: $context->currentBuild,
        ), $context);
    }

    /**
     * Executes one orchestrable namespace-level constant rename step.
     *
     * @param RenameStepContext    $context         the current rename step context
     * @param string               $constantName    the current fully-qualified constant name
     * @param string               $newConstantName the replacement short constant name
     * @param RenameConflictPolicy $conflictPolicy  the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function executeStepConstantRename(
        RenameStepContext $context,
        string $constantName,
        string $newConstantName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameStepResult {
        return $this->executeStep($this->constantRenamePlanner->plan(
            request: new ConstantRenameRequest($constantName, $newConstantName, $conflictPolicy),
            build: $context->currentBuild,
        ), $context);
    }

    /**
     * Executes one orchestrable namespace-level constant FQCN rename step.
     *
     * @param RenameStepContext    $context         the current rename step context
     * @param string               $constantName    the current fully-qualified constant name
     * @param string               $newConstantName the replacement fully-qualified constant name
     * @param RenameConflictPolicy $conflictPolicy  the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function executeStepConstantFqcnRename(
        RenameStepContext $context,
        string $constantName,
        string $newConstantName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameStepResult {
        return $this->executeStep($this->constantFqcnRenamePlanner->plan(
            request: new ConstantFqcnRenameRequest($constantName, $newConstantName, $conflictPolicy),
            build: $context->currentBuild,
        ), $context);
    }

    /**
     * Executes one orchestrable method parameter rename step.
     *
     * @param RenameStepContext    $context          the current rename step context
     * @param string               $className        the method owner FQCN
     * @param string               $methodName       the method name
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based declaration index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function executeStepMethodParameterRename(
        RenameStepContext $context,
        string $className,
        string $methodName,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameStepResult {
        return $this->executeStep($this->parameterRenamePlanner->plan(
            request: new ParameterRenameRequest($className, $methodName, $parameterName, $newParameterName, $parameterIndex, $conflictPolicy),
            build: $context->currentBuild,
        ), $context);
    }

    /**
     * Executes one orchestrable function parameter rename step.
     *
     * @param RenameStepContext    $context          the current rename step context
     * @param string               $functionName     the fully-qualified function name
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based declaration index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function executeStepFunctionParameterRename(
        RenameStepContext $context,
        string $functionName,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameStepResult {
        return $this->executeStep($this->parameterRenamePlanner->plan(
            request: new ParameterRenameRequest('', $functionName, $parameterName, $newParameterName, $parameterIndex, $conflictPolicy),
            build: $context->currentBuild,
        ), $context);
    }

    /**
     * Plans a semantic method rename.
     *
     * @param string               $className      the class name that anchors the method rename
     * @param string               $methodName     the current method name
     * @param string               $newMethodName  the replacement method name
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is empty
     */
    public function planMethodRename(
        string $className,
        string $methodName,
        string $newMethodName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenamePlan {
        return $this->methodRenamePlanner->plan(
            request: new MethodRenameRequest($className, $methodName, $newMethodName, $conflictPolicy),
            build: $this->build,
        );
    }

    /**
     * Plans and applies a semantic method rename to virtual file AST nodes.
     *
     * @param string               $className      the class name that anchors the method rename
     * @param string               $methodName     the current method name
     * @param string               $newMethodName  the replacement method name
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is empty
     */
    public function renameMethod(
        string $className,
        string $methodName,
        string $newMethodName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->renamePlanApplier->apply(
            plan: $this->planMethodRename($className, $methodName, $newMethodName, $conflictPolicy),
            build: $this->build,
        );
    }

    /**
     * Plans a semantic property rename.
     *
     * @param string               $className       the class name that anchors the property rename
     * @param string               $propertyName    the current property name
     * @param string               $newPropertyName the replacement property name
     * @param RenameConflictPolicy $conflictPolicy  the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is empty
     */
    public function planPropertyRename(
        string $className,
        string $propertyName,
        string $newPropertyName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenamePlan {
        return $this->propertyRenamePlanner->plan(
            request: new PropertyRenameRequest($className, $propertyName, $newPropertyName, $conflictPolicy),
            build: $this->build,
        );
    }

    /**
     * Plans and applies a semantic property rename to virtual file AST nodes.
     *
     * @param string               $className       the class name that anchors the property rename
     * @param string               $propertyName    the current property name
     * @param string               $newPropertyName the replacement property name
     * @param RenameConflictPolicy $conflictPolicy  the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is empty
     */
    public function renameProperty(
        string $className,
        string $propertyName,
        string $newPropertyName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->renamePlanApplier->apply(
            plan: $this->planPropertyRename($className, $propertyName, $newPropertyName, $conflictPolicy),
            build: $this->build,
        );
    }

    /**
     * Plans a semantic class-constant rename.
     *
     * @param string               $className       the class name that anchors the class-constant rename
     * @param string               $constantName    the current class-constant name
     * @param string               $newConstantName the replacement class-constant name
     * @param RenameConflictPolicy $conflictPolicy  the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is empty
     */
    public function planClassConstantRename(
        string $className,
        string $constantName,
        string $newConstantName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenamePlan {
        return $this->classConstantRenamePlanner->plan(
            request: new ClassConstantRenameRequest($className, $constantName, $newConstantName, $conflictPolicy),
            build: $this->build,
        );
    }

    /**
     * Plans and applies a semantic class-constant rename to virtual file AST nodes.
     *
     * @param string               $className       the class name that anchors the class-constant rename
     * @param string               $constantName    the current class-constant name
     * @param string               $newConstantName the replacement class-constant name
     * @param RenameConflictPolicy $conflictPolicy  the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is empty
     */
    public function renameClassConstant(
        string $className,
        string $constantName,
        string $newConstantName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->renamePlanApplier->apply(
            plan: $this->planClassConstantRename($className, $constantName, $newConstantName, $conflictPolicy),
            build: $this->build,
        );
    }

    /**
     * Plans a semantic enum-case rename.
     *
     * @param string               $enumName       the enum name that anchors the enum-case rename
     * @param string               $caseName       the current enum-case name
     * @param string               $newCaseName    the replacement enum-case name
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function planEnumCaseRename(
        string $enumName,
        string $caseName,
        string $newCaseName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenamePlan {
        return $this->enumCaseRenamePlanner->plan(
            request: new EnumCaseRenameRequest($enumName, $caseName, $newCaseName, $conflictPolicy),
            build: $this->build,
        );
    }

    /**
     * Plans and applies a semantic enum-case rename to virtual file AST nodes.
     *
     * @param string               $enumName       the enum name that anchors the enum-case rename
     * @param string               $caseName       the current enum-case name
     * @param string               $newCaseName    the replacement enum-case name
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function renameEnumCase(
        string $enumName,
        string $caseName,
        string $newCaseName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->renamePlanApplier->apply(
            plan: $this->planEnumCaseRename($enumName, $caseName, $newCaseName, $conflictPolicy),
            build: $this->build,
        );
    }

    /**
     * Plans a semantic class-like owner rename.
     *
     * @param string               $className      the current fully-qualified class-like owner name
     * @param string               $newClassName   the replacement short class-like owner name
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function planClassRename(
        string $className,
        string $newClassName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenamePlan {
        return $this->classRenamePlanner->plan(
            request: new ClassRenameRequest($className, $newClassName, $conflictPolicy),
            build: $this->build,
        );
    }

    /**
     * Plans and applies a semantic class-like owner rename to virtual file AST nodes.
     *
     * @param string               $className      the current fully-qualified class-like owner name
     * @param string               $newClassName   the replacement short class-like owner name
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function renameClass(
        string $className,
        string $newClassName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->renamePlanApplier->apply(
            plan: $this->planClassRename($className, $newClassName, $conflictPolicy),
            build: $this->build,
        );
    }

    /**
     * Plans a semantic class-like owner rename to another fully-qualified name.
     *
     * @param string               $className      the current fully-qualified class-like owner name
     * @param string               $newClassName   the replacement fully-qualified class-like owner name
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function planClassFqcnRename(
        string $className,
        string $newClassName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenamePlan {
        return $this->classFqcnRenamePlanner->plan(
            request: new ClassFqcnRenameRequest($className, $newClassName, $conflictPolicy),
            build: $this->build,
        );
    }

    /**
     * Plans and applies a semantic class-like owner FQCN rename to virtual file AST nodes.
     *
     * @param string               $className      the current fully-qualified class-like owner name
     * @param string               $newClassName   the replacement fully-qualified class-like owner name
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function renameClassFqcn(
        string $className,
        string $newClassName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->renamePlanApplier->apply(
            plan: $this->planClassFqcnRename($className, $newClassName, $conflictPolicy),
            build: $this->build,
        );
    }

    /**
     * Plans a semantic function rename.
     *
     * @param string               $functionName    the current fully-qualified function name
     * @param string               $newFunctionName the replacement short function name
     * @param RenameConflictPolicy $conflictPolicy  the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function planFunctionRename(
        string $functionName,
        string $newFunctionName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenamePlan {
        return $this->functionRenamePlanner->plan(
            request: new FunctionRenameRequest($functionName, $newFunctionName, $conflictPolicy),
            build: $this->build,
        );
    }

    /**
     * Plans and applies a semantic function rename to virtual file AST nodes.
     *
     * @param string               $functionName    the current fully-qualified function name
     * @param string               $newFunctionName the replacement short function name
     * @param RenameConflictPolicy $conflictPolicy  the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function renameFunction(
        string $functionName,
        string $newFunctionName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->renamePlanApplier->apply(
            plan: $this->planFunctionRename($functionName, $newFunctionName, $conflictPolicy),
            build: $this->build,
        );
    }

    /**
     * Plans a semantic function rename to another fully-qualified name.
     *
     * @param string               $functionName    the current fully-qualified function name
     * @param string               $newFunctionName the replacement fully-qualified function name
     * @param RenameConflictPolicy $conflictPolicy  the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function planFunctionFqcnRename(
        string $functionName,
        string $newFunctionName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenamePlan {
        return $this->functionFqcnRenamePlanner->plan(
            request: new FunctionFqcnRenameRequest($functionName, $newFunctionName, $conflictPolicy),
            build: $this->build,
        );
    }

    /**
     * Plans and applies a semantic function FQCN rename to virtual file AST nodes.
     *
     * @param string               $functionName    the current fully-qualified function name
     * @param string               $newFunctionName the replacement fully-qualified function name
     * @param RenameConflictPolicy $conflictPolicy  the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function renameFunctionFqcn(
        string $functionName,
        string $newFunctionName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->renamePlanApplier->apply(
            plan: $this->planFunctionFqcnRename($functionName, $newFunctionName, $conflictPolicy),
            build: $this->build,
        );
    }

    /**
     * Plans a semantic namespace-level constant rename.
     *
     * @param string               $constantName    the current fully-qualified constant name
     * @param string               $newConstantName the replacement short constant name
     * @param RenameConflictPolicy $conflictPolicy  the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function planConstantRename(
        string $constantName,
        string $newConstantName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenamePlan {
        return $this->constantRenamePlanner->plan(
            request: new ConstantRenameRequest($constantName, $newConstantName, $conflictPolicy),
            build: $this->build,
        );
    }

    /**
     * Plans and applies a semantic namespace-level constant rename to virtual file AST nodes.
     *
     * @param string               $constantName    the current fully-qualified constant name
     * @param string               $newConstantName the replacement short constant name
     * @param RenameConflictPolicy $conflictPolicy  the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function renameConstant(
        string $constantName,
        string $newConstantName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->renamePlanApplier->apply(
            plan: $this->planConstantRename($constantName, $newConstantName, $conflictPolicy),
            build: $this->build,
        );
    }

    /**
     * Plans a semantic namespace-level constant rename to another fully-qualified name.
     *
     * @param string               $constantName    the current fully-qualified constant name
     * @param string               $newConstantName the replacement fully-qualified constant name
     * @param RenameConflictPolicy $conflictPolicy  the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function planConstantFqcnRename(
        string $constantName,
        string $newConstantName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenamePlan {
        return $this->constantFqcnRenamePlanner->plan(
            request: new ConstantFqcnRenameRequest($constantName, $newConstantName, $conflictPolicy),
            build: $this->build,
        );
    }

    /**
     * Plans and applies a semantic namespace-level constant FQCN rename to virtual file AST nodes.
     *
     * @param string               $constantName    the current fully-qualified constant name
     * @param string               $newConstantName the replacement fully-qualified constant name
     * @param RenameConflictPolicy $conflictPolicy  the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function renameConstantFqcn(
        string $constantName,
        string $newConstantName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->renamePlanApplier->apply(
            plan: $this->planConstantFqcnRename($constantName, $newConstantName, $conflictPolicy),
            build: $this->build,
        );
    }

    /**
     * Plans a semantic method parameter rename.
     *
     * @param string               $className        the method owner FQCN
     * @param string               $methodName       the method name
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based declaration index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function planMethodParameterRename(
        string $className,
        string $methodName,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenamePlan {
        return $this->parameterRenamePlanner->plan(
            request: new ParameterRenameRequest($className, $methodName, $parameterName, $newParameterName, $parameterIndex, $conflictPolicy),
            build: $this->build,
        );
    }

    /**
     * Plans and applies a semantic method parameter rename to virtual file AST nodes.
     *
     * @param string               $className        the method owner FQCN
     * @param string               $methodName       the method name
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based declaration index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function renameMethodParameter(
        string $className,
        string $methodName,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->renamePlanApplier->apply(
            plan: $this->planMethodParameterRename($className, $methodName, $parameterName, $newParameterName, $parameterIndex, $conflictPolicy),
            build: $this->build,
        );
    }

    /**
     * Plans a semantic function parameter rename.
     *
     * @param string               $functionName     the fully-qualified function name
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based declaration index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function planFunctionParameterRename(
        string $functionName,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenamePlan {
        return $this->parameterRenamePlanner->plan(
            request: new ParameterRenameRequest('', $functionName, $parameterName, $newParameterName, $parameterIndex, $conflictPolicy),
            build: $this->build,
        );
    }

    /**
     * Plans and applies a semantic function parameter rename to virtual file AST nodes.
     *
     * @param string               $functionName     the fully-qualified function name
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based declaration index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function renameFunctionParameter(
        string $functionName,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->renamePlanApplier->apply(
            plan: $this->planFunctionParameterRename($functionName, $parameterName, $newParameterName, $parameterIndex, $conflictPolicy),
            build: $this->build,
        );
    }
}
