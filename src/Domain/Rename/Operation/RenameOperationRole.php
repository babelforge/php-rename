<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Domain\Rename\Operation;

/**
 * Enumerates why an AST node is part of a rename plan.
 */
enum RenameOperationRole
{
    case DECLARATION;
    case USAGE;
}
