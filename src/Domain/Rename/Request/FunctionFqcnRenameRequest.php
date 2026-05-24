<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Domain\Rename\Request;

use BabelForge\PhpRename\Domain\Rename\Conflict\RenameConflictPolicy;
use BabelForge\PhpRename\Domain\Rename\Validation\RenameInputValidator;

/**
 * Describes a fully-qualified function rename intent.
 */
final readonly class FunctionFqcnRenameRequest implements RenameRequestInterface
{
    /**
     * Constructor.
     *
     * @param string               $functionName    the current fully-qualified function name
     * @param string               $newFunctionName the replacement fully-qualified function name
     * @param RenameConflictPolicy $conflictPolicy  the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is empty
     */
    public function __construct(
        public string $functionName,
        public string $newFunctionName,
        public RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ) {
        RenameInputValidator::guardFqcn($functionName, 'functionName');
        RenameInputValidator::guardFqcn($newFunctionName, 'newFunctionName');
    }

    /**
     * Returns the current symbol name.
     */
    public function oldName(): string
    {
        return ltrim($this->functionName, '\\');
    }

    /**
     * Returns the replacement symbol name.
     */
    public function newName(): string
    {
        return ltrim($this->newFunctionName, '\\');
    }
}
