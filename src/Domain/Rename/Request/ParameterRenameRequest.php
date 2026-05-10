<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Domain\Rename\Request;

use PhpNoobs\PhpRename\Domain\Rename\Conflict\RenameConflictPolicy;
use PhpNoobs\PhpRename\Domain\Rename\Validation\RenameInputValidator;

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
        if ('' !== $owner) {
            RenameInputValidator::guardFqcn($owner, 'owner');
            RenameInputValidator::guardShortIdentifier($functionLikeName, 'functionLikeName');
        } else {
            RenameInputValidator::guardFqcn($functionLikeName, 'functionLikeName');
        }

        RenameInputValidator::guardShortIdentifier($parameterName, 'parameterName');
        RenameInputValidator::guardShortIdentifier($newParameterName, 'newParameterName');

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
}
