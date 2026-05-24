<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Domain\Rename\Request;

use BabelForge\PhpRename\Domain\Rename\Conflict\RenameConflictPolicy;
use BabelForge\PhpRename\Domain\Rename\Validation\RenameInputValidator;

/**
 * Describes a method rename intent anchored to a class name.
 */
final readonly class MethodRenameRequest implements RenameRequestInterface
{
    /**
     * Constructor.
     *
     * @param string               $className      the class name that anchors the method rename
     * @param string               $methodName     the current method name
     * @param string               $newMethodName  the replacement method name
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is empty
     */
    public function __construct(
        public string $className,
        public string $methodName,
        public string $newMethodName,
        public RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ) {
        RenameInputValidator::guardFqcn($className, 'className');
        RenameInputValidator::guardShortIdentifier($methodName, 'methodName');
        RenameInputValidator::guardShortIdentifier($newMethodName, 'newMethodName');
    }

    /**
     * Returns the current symbol name.
     */
    public function oldName(): string
    {
        return $this->methodName;
    }

    /**
     * Returns the replacement symbol name.
     */
    public function newName(): string
    {
        return $this->newMethodName;
    }
}
