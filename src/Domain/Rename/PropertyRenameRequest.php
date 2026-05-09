<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Domain\Rename;

/**
 * Describes a property rename intent anchored to a class name.
 */
final readonly class PropertyRenameRequest implements RenameRequestInterface
{
    /**
     * Constructor.
     *
     * @param string $className       the class name that anchors the property rename
     * @param string $propertyName    the current property name
     * @param string $newPropertyName the replacement property name
     *
     * @throws \InvalidArgumentException when one rename input is empty
     */
    public function __construct(
        public string $className,
        public string $propertyName,
        public string $newPropertyName,
    ) {
        $this->guardNotEmpty($className, 'className');
        $this->guardNotEmpty($propertyName, 'propertyName');
        $this->guardNotEmpty($newPropertyName, 'newPropertyName');
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
