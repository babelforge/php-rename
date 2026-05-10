<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Domain\Rename\Request;

/**
 * Describes a fully-qualified function rename intent.
 */
final readonly class FunctionFqcnRenameRequest implements RenameRequestInterface
{
    /**
     * Constructor.
     *
     * @param string $functionName    the current fully-qualified function name
     * @param string $newFunctionName the replacement fully-qualified function name
     *
     * @throws \InvalidArgumentException when one rename input is empty
     */
    public function __construct(
        public string $functionName,
        public string $newFunctionName,
    ) {
        $this->guardNotEmpty($functionName, 'functionName');
        $this->guardNotEmpty($newFunctionName, 'newFunctionName');
    }

    /**
     * Returns the current symbol name.
     */
    public function oldName(): string
    {
        return ltrim($this->functionName, '\\');
    }

    /**
     * Returns the replacement symbol name.
     */
    public function newName(): string
    {
        return ltrim($this->newFunctionName, '\\');
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
