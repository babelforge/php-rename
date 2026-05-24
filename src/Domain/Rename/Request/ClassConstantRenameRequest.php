<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Domain\Rename\Request;

use BabelForge\PhpRename\Domain\Rename\Conflict\RenameConflictPolicy;
use BabelForge\PhpRename\Domain\Rename\Validation\RenameInputValidator;

/**
 * Describes a class-constant rename intent anchored to a class name.
 */
final readonly class ClassConstantRenameRequest implements RenameRequestInterface
{
    /**
     * Constructor.
     *
     * @param string               $className       the class name that anchors the class-constant rename
     * @param string               $constantName    the current class-constant name
     * @param string               $newConstantName the replacement class-constant name
     * @param RenameConflictPolicy $conflictPolicy  the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is empty
     */
    public function __construct(
        public string $className,
        public string $constantName,
        public string $newConstantName,
        public RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ) {
        RenameInputValidator::guardFqcn($className, 'className');
        RenameInputValidator::guardShortIdentifier($constantName, 'constantName');
        RenameInputValidator::guardShortIdentifier($newConstantName, 'newConstantName');
    }

    /**
     * Returns the current symbol name.
     */
    public function oldName(): string
    {
        return $this->constantName;
    }

    /**
     * Returns the replacement symbol name.
     */
    public function newName(): string
    {
        return $this->newConstantName;
    }
}
