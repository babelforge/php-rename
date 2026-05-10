<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Domain\Rename\Request;

use PhpNoobs\PhpRename\Domain\Rename\Conflict\RenameConflictPolicy;

/**
 * Describes a method or function parameter rename intent.
 */
final readonly class ParameterRenameRequest implements RenameRequestInterface
{
    /**
     * Constructor.
     *
     * @param string               $owner            the owner FQCN, or an empty string for functions
     * @param string               $functionLikeName the method name or fully-qualified function name
     * @param string               $parameterName    the current parameter name without "$"
     * @param string               $newParameterName the replacement parameter name without "$"
     * @param int|null             $parameterIndex   the optional zero-based declaration index
     * @param RenameConflictPolicy $conflictPolicy   the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function __construct(
        public string $owner,
        public string $functionLikeName,
        public string $parameterName,
        public string $newParameterName,
        public ?int $parameterIndex = null,
        public RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ) {
        $this->guardNotEmpty($functionLikeName, 'functionLikeName');
        $this->guardNotEmpty($parameterName, 'parameterName');
        $this->guardNotEmpty($newParameterName, 'newParameterName');

        if (null !== $parameterIndex && 0 > $parameterIndex) {
            throw new \InvalidArgumentException('The "parameterIndex" rename input must be greater than or equal to zero.');
        }
    }

    /**
     * Returns the current symbol name.
     */
    public function oldName(): string
    {
        return $this->parameterName;
    }

    /**
     * Returns the replacement symbol name.
     */
    public function newName(): string
    {
        return $this->newParameterName;
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
