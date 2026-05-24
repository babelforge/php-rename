<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Application;

use BabelForge\PhpRename\Domain\Rename\Conflict\RenameConflictPolicy;
use BabelForge\PhpRename\Domain\Rename\Plan\RenameResult;
use BabelForge\PhpRename\Domain\Rename\Request\NestedCallableRenameRequest;

/**
 * Exposes nested callable parameter rename operations on transactions.
 */
trait NestedCallablePhpRenameTransactionMethods
{
    /**
     * Plans and applies a nested callable parameter rename within the transaction.
     *
     * @param NestedCallableRenameRequest $request the nested callable rename request
     *
     * @throws \LogicException when the transaction is no longer active
     */
    public function renameNestedCallableParameter(NestedCallableRenameRequest $request): RenameResult
    {
        return $this->execute($this->renamer()->planNestedCallableParameterRename($request));
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
     * @throws \LogicException when the transaction is no longer active
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
        return $this->execute($this->renamer()->planClosureParameterRenameInMethod(
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
     * @throws \LogicException when the transaction is no longer active
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
        return $this->execute($this->renamer()->planArrowFunctionParameterRenameInMethod(
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
     * Plans and applies a closure parameter rename inside a function container.
     *
     * @param string               $functionName     the fully-qualified function name
     * @param int                  $closureIndex     the zero-based closure index inside the function
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based declaration index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     *
     * @throws \LogicException when the transaction is no longer active
     */
    public function renameClosureParameterInFunction(
        string $functionName,
        int $closureIndex,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->execute($this->renamer()->planClosureParameterRenameInFunction(
            $functionName,
            $closureIndex,
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
     * @throws \LogicException when the transaction is no longer active
     */
    public function renameArrowFunctionParameterInFunction(
        string $functionName,
        int $arrowIndex,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->execute($this->renamer()->planArrowFunctionParameterRenameInFunction(
            $functionName,
            $arrowIndex,
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
     * @throws \LogicException when the transaction is no longer active
     */
    public function renameClosureParameterInFile(
        string $filePath,
        int $closureIndex,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->execute($this->renamer()->planClosureParameterRenameInFile(
            $filePath,
            $closureIndex,
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
     * @throws \LogicException when the transaction is no longer active
     */
    public function renameArrowFunctionParameterInFile(
        string $filePath,
        int $arrowIndex,
        string $parameterName,
        string $newParameterName,
        ?int $parameterIndex = null,
        RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ): RenameResult {
        return $this->execute($this->renamer()->planArrowFunctionParameterRenameInFile(
            $filePath,
            $arrowIndex,
            $parameterName,
            $newParameterName,
            $parameterIndex,
            $conflictPolicy,
        ));
    }
}
