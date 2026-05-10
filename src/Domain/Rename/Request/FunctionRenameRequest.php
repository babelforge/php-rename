<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Domain\Rename\Request;

use PhpNoobs\PhpRename\Domain\Rename\Conflict\RenameConflictPolicy;
use PhpNoobs\PhpRename\Domain\Rename\Validation\RenameInputValidator;

/**
 * Describes a function rename intent.
 */
final readonly class FunctionRenameRequest implements RenameRequestInterface
{
    /**
     * Constructor.
     *
     * @param string               $functionName    the current fully-qualified function name
     * @param string               $newFunctionName the replacement short function name
     * @param RenameConflictPolicy $conflictPolicy  the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is empty
     */
    public function __construct(
        public string $functionName,
        public string $newFunctionName,
        public RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ) {
        RenameInputValidator::guardFqcn($functionName, 'functionName');
        RenameInputValidator::guardShortIdentifier($newFunctionName, 'newFunctionName');
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
