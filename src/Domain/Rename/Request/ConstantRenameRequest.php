<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Domain\Rename\Request;

use PhpNoobs\PhpRename\Domain\Rename\Conflict\RenameConflictPolicy;
use PhpNoobs\PhpRename\Domain\Rename\Validation\RenameInputValidator;

/**
 * Describes a namespace-level constant rename intent.
 */
final readonly class ConstantRenameRequest implements RenameRequestInterface
{
    /**
     * Constructor.
     *
     * @param string               $constantName    the current fully-qualified constant name
     * @param string               $newConstantName the replacement short constant name
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
        RenameInputValidator::guardShortIdentifier($newConstantName, 'newConstantName');
    }

    /**
     * Returns the current symbol name.
     */
    public function oldName(): string
    {
        return $this->shortName($this->constantName);
    }

    /**
     * Returns the replacement symbol name.
     */
    public function newName(): string
    {
        return $this->newConstantName;
    }

    /**
     * Returns the short name for one constant name.
     *
     * @param string $name the constant name
     */
    private function shortName(string $name): string
    {
        $parts = explode('\\', ltrim($name, '\\'));

        return (string) end($parts);
    }
}
