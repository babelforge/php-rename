<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Domain\Rename\Request;

/**
 * Describes a class-constant rename intent anchored to a class name.
 */
final readonly class ClassConstantRenameRequest implements RenameRequestInterface
{
    /**
     * Constructor.
     *
     * @param string $className       the class name that anchors the class-constant rename
     * @param string $constantName    the current class-constant name
     * @param string $newConstantName the replacement class-constant name
     *
     * @throws \InvalidArgumentException when one rename input is empty
     */
    public function __construct(
        public string $className,
        public string $constantName,
        public string $newConstantName,
    ) {
        $this->guardNotEmpty($className, 'className');
        $this->guardNotEmpty($constantName, 'constantName');
        $this->guardNotEmpty($newConstantName, 'newConstantName');
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
