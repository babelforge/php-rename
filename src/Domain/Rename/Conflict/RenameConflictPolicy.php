<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Domain\Rename\Conflict;

/**
 * Defines how rename conflict facts must be handled during planning.
 */
enum RenameConflictPolicy
{
    /**
     * Report conflicts as diagnostics and keep the plan applicable.
     */
    case REPORT;

    /**
     * Report conflicts as errors so the plan cannot be applied.
     */
    case FAIL;
}
