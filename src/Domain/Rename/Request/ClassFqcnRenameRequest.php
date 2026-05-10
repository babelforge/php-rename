<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Domain\Rename\Request;

use PhpNoobs\PhpRename\Domain\Rename\Conflict\RenameConflictPolicy;

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
        $this->guardNotEmpty($className, 'className');
        $this->guardNotEmpty($newClassName, 'newClassName');
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

    /**
     * Ensures that a rename input is not empty.
     *
     * @param string $value the input value
     * @param string $name  the input name
     *
     * @throws \InvalidArgumentException when the input is empty
     */
    private function guardNotEmpty(string $value, string $name): void
    {
        if ('' === trim($value)) {
            throw new \InvalidArgumentException(sprintf('The "%s" rename input cannot be empty.', $name));
        }
    }
}
