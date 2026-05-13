<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Application;

use PhpNoobs\PhpRename\Domain\Rename\Conflict\RenameConflictPolicy;
use PhpNoobs\PhpRename\Domain\Rename\Plan\RenameResult;
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
        return $this->execute($this->renamer()->planClosureLocalVariableRenameInMethod(
            $className,
            $methodName,
            $closureIndex,
            $variableName,
            $newName,
            $conflictPolicy,
        ));
    }

    /**
     * Plans and applies an arrow-function local variable rename inside a method container.
     *
     * @param string               $className      the method owner FQCN
     * @param string               $methodName     the method name
     * @param int                  $arrowIndex     the zero-based arrow-function index inside the method
     * @param string               $variableName   the current variable name without "$"
     * @param string               $newName        the replacement variable name without "$"
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     *
     * @throws \LogicException when the transaction is no longer active
     */
    public function renameArrowFunctionLocalVariableInMethod(
        string $className,
        string $methodName,
        int $arrowIndex,
        string $variableName,
        string $newName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->execute($this->renamer()->planArrowFunctionLocalVariableRenameInMethod(
            $className,
            $methodName,
            $arrowIndex,
            $variableName,
            $newName,
            $conflictPolicy,
        ));
    }

    /**
     * Plans and applies a closure local variable rename inside a function container.
     *
     * @param string               $functionName   the fully-qualified function name
     * @param int                  $closureIndex   the zero-based closure index inside the function
     * @param string               $variableName   the current variable name without "$"
     * @param string               $newName        the replacement variable name without "$"
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     *
     * @throws \LogicException when the transaction is no longer active
     */
    public function renameClosureLocalVariableInFunction(
        string $functionName,
        int $closureIndex,
        string $variableName,
        string $newName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->execute($this->renamer()->planClosureLocalVariableRenameInFunction(
            $functionName,
            $closureIndex,
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
     * @throws \LogicException when the transaction is no longer active
     */
    public function renameArrowFunctionLocalVariableInFunction(
        string $functionName,
        int $arrowIndex,
        string $variableName,
        string $newName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->execute($this->renamer()->planArrowFunctionLocalVariableRenameInFunction(
            $functionName,
            $arrowIndex,
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
     * @throws \LogicException when the transaction is no longer active
     */
    public function renameClosureLocalVariableInFile(
        string $filePath,
        int $closureIndex,
        string $variableName,
        string $newName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->execute($this->renamer()->planClosureLocalVariableRenameInFile(
            $filePath,
            $closureIndex,
            $variableName,
            $newName,
            $conflictPolicy,
        ));
    }

    /**
     * Plans and applies an arrow-function local variable rename inside a file container.
     *
     * @param string               $filePath       the physical or virtual file path
     * @param int                  $arrowIndex     the zero-based arrow-function index inside the file
     * @param string               $variableName   the current variable name without "$"
     * @param string               $newName        the replacement variable name without "$"
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     *
     * @throws \LogicException when the transaction is no longer active
     */
    public function renameArrowFunctionLocalVariableInFile(
        string $filePath,
        int $arrowIndex,
        string $variableName,
        string $newName,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->execute($this->renamer()->planArrowFunctionLocalVariableRenameInFile(
            $filePath,
            $arrowIndex,
            $variableName,
            $newName,
            $conflictPolicy,
        ));
    }
}
