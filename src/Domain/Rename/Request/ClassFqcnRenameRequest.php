<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Domain\Rename\Request;

use BabelForge\PhpRename\Domain\Rename\Conflict\RenameConflictPolicy;
use BabelForge\PhpRename\Domain\Rename\Validation\RenameInputValidator;

/**
 * Describes a fully-qualified class-like owner rename intent.
 */
final readonly class ClassFqcnRenameRequest implements RenameRequestInterface
{
    /**
     * Constructor.
     *
     * @param string               $className      the current fully-qualified class-like owner name
     * @param string               $newClassName   the replacement fully-qualified class-like owner name
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is empty
     */
    public function __construct(
        public string $className,
        public string $newClassName,
        public RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ) {
        RenameInputValidator::guardFqcn($className, 'className');
        RenameInputValidator::guardFqcn($newClassName, 'newClassName');
    }

    /**
     * Returns the current symbol name.
     */
    public function oldName(): string
    {
        return ltrim($this->className, '\\');
    }

    /**
     * Returns the replacement symbol name.
     */
    public function newName(): string
    {
        return ltrim($this->newClassName, '\\');
    }
}
