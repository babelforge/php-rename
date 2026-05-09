<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Domain\Rename\Request;

/**
 * Describes a method rename intent anchored to a class name.
 */
final readonly class MethodRenameRequest implements RenameRequestInterface
{
    /**
     * Constructor.
     *
     * @param string $className     the class name that anchors the method rename
     * @param string $methodName    the current method name
     * @param string $newMethodName the replacement method name
     *
     * @throws \InvalidArgumentException when one rename input is empty
     */
    public function __construct(
        public string $className,
        public string $methodName,
        public string $newMethodName,
    ) {
        $this->guardNotEmpty($className, 'className');
        $this->guardNotEmpty($methodName, 'methodName');
        $this->guardNotEmpty($newMethodName, 'newMethodName');
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
