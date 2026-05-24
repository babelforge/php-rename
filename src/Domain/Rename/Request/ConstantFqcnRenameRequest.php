<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Domain\Rename\Request;

use BabelForge\PhpRename\Domain\Rename\Conflict\RenameConflictPolicy;
use BabelForge\PhpRename\Domain\Rename\Validation\RenameInputValidator;

/**
 * Describes a namespace-level constant FQCN rename intent.
 */
final readonly class ConstantFqcnRenameRequest implements RenameRequestInterface
{
    /**
     * Constructor.
     *
     * @param string               $constantName    the current fully-qualified constant name
     * @param string               $newConstantName the replacement fully-qualified constant name
     * @param RenameConflictPolicy $conflictPolicy  the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function __construct(
        public string $constantName,
        public string $newConstantName,
        public RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ) {
        RenameInputValidator::guardFqcn($constantName, 'constantName');
        RenameInputValidator::guardFqcn($newConstantName, 'newConstantName');
    }

    /**
     * Returns the current symbol name.
     */
    public function oldName(): string
    {
        return ltrim($this->constantName, '\\');
    }

    /**
     * Returns the replacement symbol name.
     */
    public function newName(): string
    {
        return ltrim($this->newConstantName, '\\');
    }
}
