<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Domain\Rename\Transaction;

/**
 * Enumerates rename transaction statuses.
 */
enum RenameTransactionStatus
{
    case ACTIVE;
    case FAILED;
    case COMMITTED;
    case ROLLED_BACK;
}
