<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Domain\Rename;

/**
 * Enumerates why an AST node is part of a rename plan.
 */
enum RenameOperationRole
{
    case DECLARATION;
    case USAGE;
}
