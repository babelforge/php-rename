<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Application;

use PhpNoobs\PhpRename\Domain\Rename\Conflict\RenameConflictPolicy;
use PhpNoobs\PhpRename\Domain\Rename\Plan\RenamePlan;
use PhpNoobs\PhpRename\Domain\Rename\Plan\RenameResult;
use PhpNoobs\PhpRename\Domain\Rename\Request\NestedCallableContainerKind;
use PhpNoobs\PhpRename\Domain\Rename\Request\NestedCallableKind;
use PhpNoobs\PhpRename\Domain\Rename\Request\NestedCallableLocalVariableRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\Step\RenameStepContext;
use PhpNoobs\PhpRename\Domain\Rename\Step\RenameStepResult;

/**
 * Exposes nested callable local variable rename operations on the public facade.
 */
trait NestedCallableLocalVariablePhpRenameMethods
{
    /**
     * Executes one orchestrable nested callable local variable rename step.
     *
     * @param RenameStepContext                        $context the current rename step context
     * @param NestedCallableLocalVariableRenameRequest $request the nested callable local variable rename request
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function executeStepNestedCallableLocalVariableRename(
        RenameStepContext $context,
        NestedCallableLocalVariableRenameRequest $request,
    ): RenameStepResult {
        return $this->executeStep($this->nestedCallableLocalVariableRenamePlanner->planLocalVariable($request, $context->currentBuild), $context);
    }

    /**
     * Plans a nested callable local variable rename.
     *
     * @param NestedCallableLocalVariableRenameRequest $request the nested callable local variable rename request
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function planNestedCallableLocalVariableRename(NestedCallableLocalVariableRenameRequest $request): RenamePlan
    {
        return $this->nestedCallableLocalVariableRenamePlanner->planLocalVariable($request, $this->build);
    }

    /**
     * Plans and applies a nested callable local variable rename to virtual file AST nodes.
     *
     * @param NestedCallableLocalVariableRenameRequest $request the nested callable local variable rename request
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function renameNestedCallableLocalVariable(NestedCallableLocalVariableRenameRequest $request): RenameResult
    {
        return $this->renamePlanApplier->apply(
            plan: $this->planNestedCallableLocalVariableRename($request),
            build: $this->build,
        );
    }

    /**
     * Executes one closure local variable rename step inside a method container.
     *
     * @param RenameStepContext    $context        the current rename step context
     * @param string               $className      the method owner FQCN
     * @param string               $methodName     the method name
     * @param int                  $closureIndex   the zero-based closure index inside the method
     * @param string               $variableName   the current variable name without "$"
     * @param string               $newName        the replacement variable name without "$"
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function executeStepClosureLocalVariableRenameInMethod(
        RenameStepContext $context,
        string $className,
        string $methodName,
        int $closureIndex,
        string $variableName,
        string $newName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameStepResult {
        return $this->executeStepNestedCallableLocalVariableRename($context, $this->methodNestedCallableLocalVariableRequest(
            NestedCallableKind::CLOSURE,
            $className,
            $methodName,
            $closureIndex,
            $variableName,
            $newName,
            $conflictPolicy,
        ));
    }

    /**
     * Plans and applies a closure local variable rename inside a method container.
     *
     * @param string               $className      the method owner FQCN
     * @param string               $methodName     the method name
     * @param int                  $closureIndex   the zero-based closure index inside the method
     * @param string               $variableName   the current variable name without "$"
     * @param string               $newName        the replacement variable name without "$"
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function renameClosureLocalVariableInMethod(
        string $className,
        string $methodName,
        int $closureIndex,
        string $variableName,
        string $newName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->renameNestedCallableLocalVariable($this->methodNestedCallableLocalVariableRequest(
            NestedCallableKind::CLOSURE,
            $className,
            $methodName,
            $closureIndex,
            $variableName,
            $newName,
            $conflictPolicy,
        ));
    }

    /**
     * Executes one arrow-function local variable rename step inside a function container.
     *
     * @param RenameStepContext    $context        the current rename step context
     * @param string               $functionName   the fully-qualified function name
     * @param int                  $arrowIndex     the zero-based arrow-function index inside the function
     * @param string               $variableName   the current variable name without "$"
     * @param string               $newName        the replacement variable name without "$"
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function executeStepArrowFunctionLocalVariableRenameInFunction(
        RenameStepContext $context,
        string $functionName,
        int $arrowIndex,
        string $variableName,
        string $newName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameStepResult {
        return $this->executeStepNestedCallableLocalVariableRename($context, $this->functionNestedCallableLocalVariableRequest(
            NestedCallableKind::ARROW_FUNCTION,
            $functionName,
            $arrowIndex,
            $variableName,
            $newName,
            $conflictPolicy,
        ));
    }

    /**
     * Plans and applies an arrow-function local variable rename inside a function container.
     *
     * @param string               $functionName   the fully-qualified function name
     * @param int                  $arrowIndex     the zero-based arrow-function index inside the function
     * @param string               $variableName   the current variable name without "$"
     * @param string               $newName        the replacement variable name without "$"
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function renameArrowFunctionLocalVariableInFunction(
        string $functionName,
        int $arrowIndex,
        string $variableName,
        string $newName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->renameNestedCallableLocalVariable($this->functionNestedCallableLocalVariableRequest(
            NestedCallableKind::ARROW_FUNCTION,
            $functionName,
            $arrowIndex,
            $variableName,
            $newName,
            $conflictPolicy,
        ));
    }

    /**
     * Executes one closure local variable rename step inside a file container.
     *
     * @param RenameStepContext    $context        the current rename step context
     * @param string               $filePath       the physical or virtual file path
     * @param int                  $closureIndex   the zero-based closure index inside the file
     * @param string               $variableName   the current variable name without "$"
     * @param string               $newName        the replacement variable name without "$"
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function executeStepClosureLocalVariableRenameInFile(
        RenameStepContext $context,
        string $filePath,
        int $closureIndex,
        string $variableName,
        string $newName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameStepResult {
        return $this->executeStepNestedCallableLocalVariableRename($context, $this->fileNestedCallableLocalVariableRequest(
            NestedCallableKind::CLOSURE,
            $filePath,
            $closureIndex,
            $variableName,
            $newName,
            $conflictPolicy,
        ));
    }

    /**
     * Plans and applies a closure local variable rename inside a file container.
     *
     * @param string               $filePath       the physical or virtual file path
     * @param int                  $closureIndex   the zero-based closure index inside the file
     * @param string               $variableName   the current variable name without "$"
     * @param string               $newName        the replacement variable name without "$"
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function renameClosureLocalVariableInFile(
        string $filePath,
        int $closureIndex,
        string $variableName,
        string $newName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->renameNestedCallableLocalVariable($this->fileNestedCallableLocalVariableRequest(
            NestedCallableKind::CLOSURE,
            $filePath,
            $closureIndex,
            $variableName,
            $newName,
            $conflictPolicy,
        ));
    }

    /**
     * Creates a nested callable local variable request scoped to a method container.
     *
     * @param NestedCallableKind   $callableKind   the nested callable kind
     * @param string               $className      the method owner FQCN
     * @param string               $methodName     the method name
     * @param int                  $callableIndex  the zero-based callable index
     * @param string               $variableName   the current variable name without "$"
     * @param string               $newName        the replacement variable name without "$"
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    private function methodNestedCallableLocalVariableRequest(
        NestedCallableKind $callableKind,
        string $className,
        string $methodName,
        int $callableIndex,
        string $variableName,
        string $newName,
        RenameConflictPolicy $conflictPolicy,
    ): NestedCallableLocalVariableRenameRequest {
        return new NestedCallableLocalVariableRenameRequest(
            containerKind: NestedCallableContainerKind::METHOD,
            callableKind: $callableKind,
            callableIndex: $callableIndex,
            variableName: $variableName,
            newName: $newName,
            className: $className,
            methodName: $methodName,
            conflictPolicy: $conflictPolicy,
        );
    }

    /**
     * Creates a nested callable local variable request scoped to a function container.
     *
     * @param NestedCallableKind   $callableKind   the nested callable kind
     * @param string               $functionName   the fully-qualified function name
     * @param int                  $callableIndex  the zero-based callable index
     * @param string               $variableName   the current variable name without "$"
     * @param string               $newName        the replacement variable name without "$"
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    private function functionNestedCallableLocalVariableRequest(
        NestedCallableKind $callableKind,
        string $functionName,
        int $callableIndex,
        string $variableName,
        string $newName,
        RenameConflictPolicy $conflictPolicy,
    ): NestedCallableLocalVariableRenameRequest {
        return new NestedCallableLocalVariableRenameRequest(
            containerKind: NestedCallableContainerKind::FUNCTION,
            callableKind: $callableKind,
            callableIndex: $callableIndex,
            variableName: $variableName,
            newName: $newName,
            functionName: $functionName,
            conflictPolicy: $conflictPolicy,
        );
    }

    /**
     * Creates a nested callable local variable request scoped to a file container.
     *
     * @param NestedCallableKind   $callableKind   the nested callable kind
     * @param string               $filePath       the physical or virtual file path
     * @param int                  $callableIndex  the zero-based callable index
     * @param string               $variableName   the current variable name without "$"
     * @param string               $newName        the replacement variable name without "$"
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    private function fileNestedCallableLocalVariableRequest(
        NestedCallableKind $callableKind,
        string $filePath,
        int $callableIndex,
        string $variableName,
        string $newName,
        RenameConflictPolicy $conflictPolicy,
    ): NestedCallableLocalVariableRenameRequest {
        return new NestedCallableLocalVariableRenameRequest(
            containerKind: NestedCallableContainerKind::FILE,
            callableKind: $callableKind,
            callableIndex: $callableIndex,
            variableName: $variableName,
            newName: $newName,
            filePath: $filePath,
            conflictPolicy: $conflictPolicy,
        );
    }
}
