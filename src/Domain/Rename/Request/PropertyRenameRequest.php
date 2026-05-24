<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Domain\Rename\Request;

use BabelForge\PhpRename\Domain\Rename\Conflict\RenameConflictPolicy;
use BabelForge\PhpRename\Domain\Rename\Validation\RenameInputValidator;

/**
 * Describes a property rename intent anchored to a class name.
 */
final readonly class PropertyRenameRequest implements RenameRequestInterface
{
    /**
     * Constructor.
     *
     * @param string               $className       the class name that anchors the property rename
     * @param string               $propertyName    the current property name
     * @param string               $newPropertyName the replacement property name
     * @param RenameConflictPolicy $conflictPolicy  the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is empty
     */
    public function __construct(
        public string $className,
        public string $propertyName,
        public string $newPropertyName,
        public RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ) {
        RenameInputValidator::guardFqcn($className, 'className');
        RenameInputValidator::guardShortIdentifier($propertyName, 'propertyName');
        RenameInputValidator::guardShortIdentifier($newPropertyName, 'newPropertyName');
    }

    /**
     * Returns the current symbol name.
     */
    public function oldName(): string
    {
        return $this->propertyName;
    }

    /**
     * Returns the replacement symbol name.
     */
    public function newName(): string
    {
        return $this->newPropertyName;
    }
}
