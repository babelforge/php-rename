<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Application;

use PhpNoobs\PhpRename\Domain\Rename\Conflict\RenameConflictPolicy;
use PhpNoobs\PhpRename\Domain\Rename\Plan\RenameResult;
use PhpNoobs\PhpRename\Domain\Rename\Request\NestedCallableContainerKind;
use PhpNoobs\PhpRename\Domain\Rename\Request\NestedCallableKind;
use PhpNoobs\PhpRename\Domain\Rename\Request\NestedCallableLocalVariableRenameRequest;

/**
 * Exposes nested callable local variable rename operations on transactions.
 */
trait NestedCallableLocalVariablePhpRenameTransactionMethods
{
    /**
     * Plans and applies a nested callable local variable rename within the transaction.
     *
     * @param NestedCallableLocalVariableRenameRequest $request the nested callable local variable rename request
     *
     * @throws \LogicException when the transaction is no longer active
     */
    public function renameNestedCallableLocalVariable(NestedCallableLocalVariableRenameRequest $request): RenameResult
    {
        return $this->execute($this->renamer()->planNestedCallableLocalVariableRename($request));
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
     * @throws \LogicException when the transaction is no longer active
     */
    public function renameClosureLocalVariableInMethod(
        string $className,
        string $methodName,
        int $closureIndex,
        string $variableName,
        string $newName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->execute($this->renamer()->planNestedCallableLocalVariableRename(new NestedCallableLocalVariableRenameRequest(
            containerKind: NestedCallableContainerKind::METHOD,
            callableKind: NestedCallableKind::CLOSURE,
            callableIndex: $closureIndex,
            variableName: $variableName,
            newName: $newName,
            className: $className,
            methodName: $methodName,
            conflictPolicy: $conflictPolicy,
        )));
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
     * @throws \LogicException when the transaction is no longer active
     */
    public function renameArrowFunctionLocalVariableInFunction(
        string $functionName,
        int $arrowIndex,
        string $variableName,
        string $newName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->execute($this->renamer()->planNestedCallableLocalVariableRename(new NestedCallableLocalVariableRenameRequest(
            containerKind: NestedCallableContainerKind::FUNCTION,
            callableKind: NestedCallableKind::ARROW_FUNCTION,
            callableIndex: $arrowIndex,
            variableName: $variableName,
            newName: $newName,
            functionName: $functionName,
            conflictPolicy: $conflictPolicy,
        )));
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
     * @throws \LogicException when the transaction is no longer active
     */
    public function renameClosureLocalVariableInFile(
        string $filePath,
        int $closureIndex,
        string $variableName,
        string $newName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->execute($this->renamer()->planNestedCallableLocalVariableRename(new NestedCallableLocalVariableRenameRequest(
            containerKind: NestedCallableContainerKind::FILE,
            callableKind: NestedCallableKind::CLOSURE,
            callableIndex: $closureIndex,
            variableName: $variableName,
            newName: $newName,
            filePath: $filePath,
            conflictPolicy: $conflictPolicy,
        )));
    }
}
