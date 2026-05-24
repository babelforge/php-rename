<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Domain\Rename\Request;

use BabelForge\PhpRename\Domain\Rename\Conflict\RenameConflictPolicy;
use BabelForge\PhpRename\Domain\Rename\Validation\RenameInputValidator;

/**
 * Describes a class-like owner rename intent.
 */
final readonly class ClassRenameRequest implements RenameRequestInterface
{
    /**
     * Constructor.
     *
     * @param string               $className      the current fully-qualified class-like owner name
     * @param string               $newClassName   the replacement short class-like owner name
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function __construct(
        public string $className,
        public string $newClassName,
        public RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ) {
        RenameInputValidator::guardFqcn($className, 'className');
        RenameInputValidator::guardShortIdentifier($newClassName, 'newClassName');
    }

    /**
     * Returns the current symbol name.
     */
    public function oldName(): string
    {
        return $this->shortClassName($this->className);
    }

    /**
     * Returns the replacement symbol name.
     */
    public function newName(): string
    {
        return $this->newClassName;
    }

    /**
     * Returns the short name for one class-like owner name.
     *
     * @param string $className the class-like owner name
     */
    private function shortClassName(string $className): string
    {
        $parts = explode('\\', $className);

        return (string) end($parts);
    }
}
