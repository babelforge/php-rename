<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Domain\Rename\Request;

/**
 * Describes a rename request that can be represented by a rename plan.
 */
interface RenameRequestInterface
{
    /**
     * Returns the current symbol name.
     */
    public function oldName(): string;

    /**
     * Returns the replacement symbol name.
     */
    public function newName(): string;
}
