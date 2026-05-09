<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Domain\Rename\Request;

/**
 * Describes a class-like owner rename intent.
 */
final readonly class ClassRenameRequest implements RenameRequestInterface
{
    /**
     * Constructor.
     *
     * @param string $className    the current fully-qualified class-like owner name
     * @param string $newClassName the replacement short class-like owner name
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function __construct(
        public string $className,
        public string $newClassName,
    ) {
        $this->guardNotEmpty($className, 'className');
        $this->guardNotEmpty($newClassName, 'newClassName');

        if (str_contains($newClassName, '\\')) {
            throw new \InvalidArgumentException('The "newClassName" rename input must be a short class name.');
        }
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
