<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Domain\Rename\Request;

use PhpNoobs\PhpRename\Domain\Rename\Conflict\RenameConflictPolicy;
use PhpNoobs\PhpRename\Domain\Rename\Validation\RenameInputValidator;

/**
 * Describes an enum-case rename intent anchored to an enum name.
 */
final readonly class EnumCaseRenameRequest implements RenameRequestInterface
{
    /**
     * Constructor.
     *
     * @param string               $enumName       the enum name that anchors the enum-case rename
     * @param string               $caseName       the current enum-case name
     * @param string               $newCaseName    the replacement enum-case name
     * @param RenameConflictPolicy $conflictPolicy the rename conflict policy
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function __construct(
        public string $enumName,
        public string $caseName,
        public string $newCaseName,
        public RenameConflictPolicy $conflictPolicy = RenameConflictPolicy::FAIL,
    ) {
        RenameInputValidator::guardFqcn($enumName, 'enumName');
        RenameInputValidator::guardShortIdentifier($caseName, 'caseName');
        RenameInputValidator::guardShortIdentifier($newCaseName, 'newCaseName');
    }

    /**
     * Returns the current symbol name.
     */
    public function oldName(): string
    {
        return $this->caseName;
    }

    /**
     * Returns the replacement symbol name.
     */
    public function newName(): string
    {
        return $this->newCaseName;
    }
}
