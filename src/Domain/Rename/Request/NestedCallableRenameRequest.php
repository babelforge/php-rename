<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Domain\Rename\Request;

use PhpNoobs\PhpRename\Domain\Rename\Conflict\RenameConflictPolicy;
use PhpNoobs\PhpRename\Domain\Rename\Validation\RenameInputValidator;

/**
 * Describes a parameter rename inside a nested closure or arrow function.
 */
final readonly class NestedCallableRenameRequest implements RenameRequestInterface
{
    /**
     * Constructor.
     *
     * @param NestedCallableContainerKind $containerKind  the container kind
     * @param NestedCallableKind          $callableKind   the nested callable kind
     * @param int                         $callableIndex  the zero-based callable index inside the container
     * @param string                      $parameterName  the current parameter name without "$"
     * @param string                      $newName        the replacement parameter name without "$"
     * @param string|null                 $className      the method owner FQCN
     * @param string|null                 $methodName     the method name
     * @param string|null                 $functionName   the fully-qualified function name
     * @param string|null                 $filePath       the physical or virtual file path
     * @param int|null                    $parameterIndex the optional zero-based parameter index
     * @param RenameConflictPolicy        $conflictPolicy the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function __construct(
        public NestedCallableContainerKind $containerKind,
        public NestedCallableKind $callableKind,
        public int $callableIndex,
        public string $parameterName,
        public string $newName,
        public ?string $className = null,
        public ?string $methodName = null,
        public ?string $functionName = null,
        public ?string $filePath = null,
        public ?int $parameterIndex = null,
        public RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ) {
        RenameInputValidator::guardNonNegativeIndex($callableIndex, 'callableIndex');
        RenameInputValidator::guardShortIdentifier($parameterName, 'parameterName');
        RenameInputValidator::guardShortIdentifier($newName, 'newName');
        RenameInputValidator::guardParameterIndex($parameterIndex, 'parameterIndex');

        if (NestedCallableContainerKind::METHOD === $containerKind) {
            RenameInputValidator::guardFqcn((string) $className, 'className');
            RenameInputValidator::guardShortIdentifier((string) $methodName, 'methodName');
        }

        if (NestedCallableContainerKind::FUNCTION === $containerKind) {
            RenameInputValidator::guardFqcn((string) $functionName, 'functionName');
        }

        if (NestedCallableContainerKind::FILE === $containerKind && (null === $filePath || '' === trim($filePath))) {
            throw new \InvalidArgumentException('The "filePath" rename input cannot be empty.');
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
        return $this->newName;
    }
}
