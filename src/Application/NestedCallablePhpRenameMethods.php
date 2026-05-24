<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Application;

use BabelForge\PhpRename\Domain\Rename\Conflict\RenameConflictPolicy;
use BabelForge\PhpRename\Domain\Rename\Plan\RenamePlan;
use BabelForge\PhpRename\Domain\Rename\Plan\RenameResult;
use BabelForge\PhpRename\Domain\Rename\Request\NestedCallableContainerKind;
use BabelForge\PhpRename\Domain\Rename\Request\NestedCallableKind;
use BabelForge\PhpRename\Domain\Rename\Request\NestedCallableRenameRequest;
use BabelForge\PhpRename\Domain\Rename\Step\RenameStepContext;
use BabelForge\PhpRename\Domain\Rename\Step\RenameStepResult;

/**
 * Exposes nested callable parameter rename operations on the public facade.
 */
trait NestedCallablePhpRenameMethods
{
    /**
     * Executes one orchestrable nested callable parameter rename step.
     *
     * @param RenameStepContext           $context the current rename step context
     * @param NestedCallableRenameRequest $request the nested callable rename request
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function executeStepNestedCallableParameterRename(
        RenameStepContext $context,
        NestedCallableRenameRequest $request,
    ): RenameStepResult {
        return $this->executeStep($this->nestedCallableRenamePlanner->plan($request, $context->currentBuild), $context);
    }

    /**
     * Plans a nested callable parameter rename.
     *
     * @param NestedCallableRenameRequest $request the nested callable rename request
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function planNestedCallableParameterRename(NestedCallableRenameRequest $request): RenamePlan
    {
        return $this->nestedCallableRenamePlanner->plan($request, $this->build);
    }

    /**
     * Plans and applies a nested callable parameter rename to virtual file AST nodes.
     *
     * @param NestedCallableRenameRequest $request the nested callable rename request
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function renameNestedCallableParameter(NestedCallableRenameRequest $request): RenameResult
    {
        return $this->renamePlanApplier->apply(
            plan: $this->planNestedCallableParameterRename($request),
            build: $this->build,
        );
    }

    /**
     * Executes one closure parameter rename step inside a method container.
     *
     * @param RenameStepContext    $context          the current rename step context
     * @param string               $className        the method owner FQCN
     * @param string               $methodName       the method name
     * @param int                  $closureIndex     the zero-based closure index inside the method
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based declaration index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function executeStepClosureParameterRenameInMethod(
        RenameStepContext $context,
        string $className,
        string $methodName,
        int $closureIndex,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameStepResult {
        return $this->executeStepNestedCallableParameterRename($context, $this->methodNestedCallableRequest(
            NestedCallableKind::CLOSURE,
            $className,
            $methodName,
            $closureIndex,
            $parameterName,
            $newParameterName,
            $parameterIndex,
            $conflictPolicy,
        ));
    }

    /**
     * Plans a closure parameter rename inside a method container.
     *
     * @param string               $className        the method owner FQCN
     * @param string               $methodName       the method name
     * @param int                  $closureIndex     the zero-based closure index inside the method
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based declaration index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function planClosureParameterRenameInMethod(
        string $className,
        string $methodName,
        int $closureIndex,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenamePlan {
        return $this->planNestedCallableParameterRename($this->methodNestedCallableRequest(
            NestedCallableKind::CLOSURE,
            $className,
            $methodName,
            $closureIndex,
            $parameterName,
            $newParameterName,
            $parameterIndex,
            $conflictPolicy,
        ));
    }

    /**
     * Plans and applies a closure parameter rename inside a method container.
     *
     * @param string               $className        the method owner FQCN
     * @param string               $methodName       the method name
     * @param int                  $closureIndex     the zero-based closure index inside the method
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based declaration index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function renameClosureParameterInMethod(
        string $className,
        string $methodName,
        int $closureIndex,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->renameNestedCallableParameter($this->methodNestedCallableRequest(
            NestedCallableKind::CLOSURE,
            $className,
            $methodName,
            $closureIndex,
            $parameterName,
            $newParameterName,
            $parameterIndex,
            $conflictPolicy,
        ));
    }

    /**
     * Executes one arrow-function parameter rename step inside a method container.
     *
     * @param RenameStepContext    $context          the current rename step context
     * @param string               $className        the method owner FQCN
     * @param string               $methodName       the method name
     * @param int                  $arrowIndex       the zero-based arrow-function index inside the method
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based declaration index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function executeStepArrowFunctionParameterRenameInMethod(
        RenameStepContext $context,
        string $className,
        string $methodName,
        int $arrowIndex,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameStepResult {
        return $this->executeStepNestedCallableParameterRename($context, $this->methodNestedCallableRequest(
            NestedCallableKind::ARROW_FUNCTION,
            $className,
            $methodName,
            $arrowIndex,
            $parameterName,
            $newParameterName,
            $parameterIndex,
            $conflictPolicy,
        ));
    }

    /**
     * Plans an arrow-function parameter rename inside a method container.
     *
     * @param string               $className        the method owner FQCN
     * @param string               $methodName       the method name
     * @param int                  $arrowIndex       the zero-based arrow-function index inside the method
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based declaration index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function planArrowFunctionParameterRenameInMethod(
        string $className,
        string $methodName,
        int $arrowIndex,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenamePlan {
        return $this->planNestedCallableParameterRename($this->methodNestedCallableRequest(
            NestedCallableKind::ARROW_FUNCTION,
            $className,
            $methodName,
            $arrowIndex,
            $parameterName,
            $newParameterName,
            $parameterIndex,
            $conflictPolicy,
        ));
    }

    /**
     * Plans and applies an arrow-function parameter rename inside a method container.
     *
     * @param string               $className        the method owner FQCN
     * @param string               $methodName       the method name
     * @param int                  $arrowIndex       the zero-based arrow-function index inside the method
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based declaration index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function renameArrowFunctionParameterInMethod(
        string $className,
        string $methodName,
        int $arrowIndex,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->renameNestedCallableParameter($this->methodNestedCallableRequest(
            NestedCallableKind::ARROW_FUNCTION,
            $className,
            $methodName,
            $arrowIndex,
            $parameterName,
            $newParameterName,
            $parameterIndex,
            $conflictPolicy,
        ));
    }

    /**
     * Executes one closure parameter rename step inside a function container.
     *
     * @param RenameStepContext    $context          the current rename step context
     * @param string               $functionName     the fully-qualified function name
     * @param int                  $closureIndex     the zero-based closure index inside the function
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based declaration index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function executeStepClosureParameterRenameInFunction(
        RenameStepContext $context,
        string $functionName,
        int $closureIndex,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameStepResult {
        return $this->executeStepNestedCallableParameterRename($context, $this->functionNestedCallableRequest(
            NestedCallableKind::CLOSURE,
            $functionName,
            $closureIndex,
            $parameterName,
            $newParameterName,
            $parameterIndex,
            $conflictPolicy,
        ));
    }

    /**
     * Plans a closure parameter rename inside a function container.
     *
     * @param string               $functionName     the fully-qualified function name
     * @param int                  $closureIndex     the zero-based closure index inside the function
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based declaration index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function planClosureParameterRenameInFunction(
        string $functionName,
        int $closureIndex,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenamePlan {
        return $this->planNestedCallableParameterRename($this->functionNestedCallableRequest(
            NestedCallableKind::CLOSURE,
            $functionName,
            $closureIndex,
            $parameterName,
            $newParameterName,
            $parameterIndex,
            $conflictPolicy,
        ));
    }

    /**
     * Plans and applies a closure parameter rename inside a function container.
     *
     * @param string               $functionName     the fully-qualified function name
     * @param int                  $closureIndex     the zero-based closure index inside the function
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based declaration index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function renameClosureParameterInFunction(
        string $functionName,
        int $closureIndex,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->renameNestedCallableParameter($this->functionNestedCallableRequest(
            NestedCallableKind::CLOSURE,
            $functionName,
            $closureIndex,
            $parameterName,
            $newParameterName,
            $parameterIndex,
            $conflictPolicy,
        ));
    }

    /**
     * Executes one arrow-function parameter rename step inside a function container.
     *
     * @param RenameStepContext    $context          the current rename step context
     * @param string               $functionName     the fully-qualified function name
     * @param int                  $arrowIndex       the zero-based arrow-function index inside the function
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based declaration index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function executeStepArrowFunctionParameterRenameInFunction(
        RenameStepContext $context,
        string $functionName,
        int $arrowIndex,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameStepResult {
        return $this->executeStepNestedCallableParameterRename($context, $this->functionNestedCallableRequest(
            NestedCallableKind::ARROW_FUNCTION,
            $functionName,
            $arrowIndex,
            $parameterName,
            $newParameterName,
            $parameterIndex,
            $conflictPolicy,
        ));
    }

    /**
     * Plans an arrow-function parameter rename inside a function container.
     *
     * @param string               $functionName     the fully-qualified function name
     * @param int                  $arrowIndex       the zero-based arrow-function index inside the function
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based declaration index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function planArrowFunctionParameterRenameInFunction(
        string $functionName,
        int $arrowIndex,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenamePlan {
        return $this->planNestedCallableParameterRename($this->functionNestedCallableRequest(
            NestedCallableKind::ARROW_FUNCTION,
            $functionName,
            $arrowIndex,
            $parameterName,
            $newParameterName,
            $parameterIndex,
            $conflictPolicy,
        ));
    }

    /**
     * Plans and applies an arrow-function parameter rename inside a function container.
     *
     * @param string               $functionName     the fully-qualified function name
     * @param int                  $arrowIndex       the zero-based arrow-function index inside the function
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based declaration index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function renameArrowFunctionParameterInFunction(
        string $functionName,
        int $arrowIndex,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->renameNestedCallableParameter($this->functionNestedCallableRequest(
            NestedCallableKind::ARROW_FUNCTION,
            $functionName,
            $arrowIndex,
            $parameterName,
            $newParameterName,
            $parameterIndex,
            $conflictPolicy,
        ));
    }

    /**
     * Executes one closure parameter rename step inside a file container.
     *
     * @param RenameStepContext    $context          the current rename step context
     * @param string               $filePath         the physical or virtual file path
     * @param int                  $closureIndex     the zero-based closure index inside the file
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based declaration index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function executeStepClosureParameterRenameInFile(
        RenameStepContext $context,
        string $filePath,
        int $closureIndex,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameStepResult {
        return $this->executeStepNestedCallableParameterRename($context, $this->fileNestedCallableRequest(
            NestedCallableKind::CLOSURE,
            $filePath,
            $closureIndex,
            $parameterName,
            $newParameterName,
            $parameterIndex,
            $conflictPolicy,
        ));
    }

    /**
     * Plans a closure parameter rename inside a file container.
     *
     * @param string               $filePath         the physical or virtual file path
     * @param int                  $closureIndex     the zero-based closure index inside the file
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based declaration index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function planClosureParameterRenameInFile(
        string $filePath,
        int $closureIndex,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenamePlan {
        return $this->planNestedCallableParameterRename($this->fileNestedCallableRequest(
            NestedCallableKind::CLOSURE,
            $filePath,
            $closureIndex,
            $parameterName,
            $newParameterName,
            $parameterIndex,
            $conflictPolicy,
        ));
    }

    /**
     * Plans and applies a closure parameter rename inside a file container.
     *
     * @param string               $filePath         the physical or virtual file path
     * @param int                  $closureIndex     the zero-based closure index inside the file
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based declaration index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function renameClosureParameterInFile(
        string $filePath,
        int $closureIndex,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->renameNestedCallableParameter($this->fileNestedCallableRequest(
            NestedCallableKind::CLOSURE,
            $filePath,
            $closureIndex,
            $parameterName,
            $newParameterName,
            $parameterIndex,
            $conflictPolicy,
        ));
    }

    /**
     * Executes one arrow-function parameter rename step inside a file container.
     *
     * @param RenameStepContext    $context          the current rename step context
     * @param string               $filePath         the physical or virtual file path
     * @param int                  $arrowIndex       the zero-based arrow-function index inside the file
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based declaration index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function executeStepArrowFunctionParameterRenameInFile(
        RenameStepContext $context,
        string $filePath,
        int $arrowIndex,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameStepResult {
        return $this->executeStepNestedCallableParameterRename($context, $this->fileNestedCallableRequest(
            NestedCallableKind::ARROW_FUNCTION,
            $filePath,
            $arrowIndex,
            $parameterName,
            $newParameterName,
            $parameterIndex,
            $conflictPolicy,
        ));
    }

    /**
     * Plans an arrow-function parameter rename inside a file container.
     *
     * @param string               $filePath         the physical or virtual file path
     * @param int                  $arrowIndex       the zero-based arrow-function index inside the file
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based declaration index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function planArrowFunctionParameterRenameInFile(
        string $filePath,
        int $arrowIndex,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenamePlan {
        return $this->planNestedCallableParameterRename($this->fileNestedCallableRequest(
            NestedCallableKind::ARROW_FUNCTION,
            $filePath,
            $arrowIndex,
            $parameterName,
            $newParameterName,
            $parameterIndex,
            $conflictPolicy,
        ));
    }

    /**
     * Plans and applies an arrow-function parameter rename inside a file container.
     *
     * @param string               $filePath         the physical or virtual file path
     * @param int                  $arrowIndex       the zero-based arrow-function index inside the file
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based declaration index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function renameArrowFunctionParameterInFile(
        string $filePath,
        int $arrowIndex,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->renameNestedCallableParameter($this->fileNestedCallableRequest(
            NestedCallableKind::ARROW_FUNCTION,
            $filePath,
            $arrowIndex,
            $parameterName,
            $newParameterName,
            $parameterIndex,
            $conflictPolicy,
        ));
    }

    /**
     * Creates a nested callable request scoped to a method container.
     *
     * @param NestedCallableKind   $callableKind     the nested callable kind
     * @param string               $className        the method owner FQCN
     * @param string               $methodName       the method name
     * @param int                  $callableIndex    the zero-based callable index
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based declaration index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    private function methodNestedCallableRequest(
        NestedCallableKind $callableKind,
        string $className,
        string $methodName,
        int $callableIndex,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex,
        RenameConflictPolicy $conflictPolicy,
    ): NestedCallableRenameRequest {
        return new NestedCallableRenameRequest(
            containerKind: NestedCallableContainerKind::METHOD,
            callableKind: $callableKind,
            callableIndex: $callableIndex,
            parameterName: $parameterName,
            newName: $newParameterName,
            className: $className,
            methodName: $methodName,
            parameterIndex: $parameterIndex,
            conflictPolicy: $conflictPolicy,
        );
    }

    /**
     * Creates a nested callable request scoped to a function container.
     *
     * @param NestedCallableKind   $callableKind     the nested callable kind
     * @param string               $functionName     the fully-qualified function name
     * @param int                  $callableIndex    the zero-based callable index
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based declaration index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    private function functionNestedCallableRequest(
        NestedCallableKind $callableKind,
        string $functionName,
        int $callableIndex,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex,
        RenameConflictPolicy $conflictPolicy,
    ): NestedCallableRenameRequest {
        return new NestedCallableRenameRequest(
            containerKind: NestedCallableContainerKind::FUNCTION,
            callableKind: $callableKind,
            callableIndex: $callableIndex,
            parameterName: $parameterName,
            newName: $newParameterName,
            functionName: $functionName,
            parameterIndex: $parameterIndex,
            conflictPolicy: $conflictPolicy,
        );
    }

    /**
     * Creates a nested callable request scoped to a file container.
     *
     * @param NestedCallableKind   $callableKind     the nested callable kind
     * @param string               $filePath         the physical or virtual file path
     * @param int                  $callableIndex    the zero-based callable index
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based declaration index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    private function fileNestedCallableRequest(
        NestedCallableKind $callableKind,
        string $filePath,
        int $callableIndex,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex,
        RenameConflictPolicy $conflictPolicy,
    ): NestedCallableRenameRequest {
        return new NestedCallableRenameRequest(
            containerKind: NestedCallableContainerKind::FILE,
            callableKind: $callableKind,
            callableIndex: $callableIndex,
            parameterName: $parameterName,
            newName: $newParameterName,
            filePath: $filePath,
            parameterIndex: $parameterIndex,
            conflictPolicy: $conflictPolicy,
        );
    }
}
