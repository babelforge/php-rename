<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Domain\Rename\Request;

/**
 * Describes a function rename intent.
 */
final readonly class FunctionRenameRequest implements RenameRequestInterface
{
    /**
     * Constructor.
     *
     * @param string $functionName    the current fully-qualified function name
     * @param string $newFunctionName the replacement short function name
     *
     * @throws \InvalidArgumentException when one rename input is empty
     */
    public function __construct(
        public string $functionName,
        public string $newFunctionName,
    ) {
        $this->guardNotEmpty($functionName, 'functionName');
        $this->guardNotEmpty($newFunctionName, 'newFunctionName');

        if (str_contains($newFunctionName, '\\')) {
            throw new \InvalidArgumentException('The "newFunctionName" rename input must be a short function name.');
        }
    }

    /**
     * Returns the current symbol name.
     */
    public function oldName(): string
    {
        return $this->shortFunctionName($this->functionName);
    }

    /**
     * Returns the replacement symbol name.
     */
    public function newName(): string
    {
        return $this->newFunctionName;
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
     * Returns the short name for one function name.
     *
     * @param string $functionName the function name
     */
    private function shortFunctionName(string $functionName): string
    {
        $parts = explode('\\', $functionName);

        return (string) end($parts);
    }
}
