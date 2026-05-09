<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Domain\Rename;

/**
 * Enumerates supported rename symbol kinds.
 */
enum RenameSymbolKind
{
    case CLASS_;
    case FUNCTION_;
    case METHOD;
    case PROPERTY;
    case CLASS_CONSTANT;
    case PARAMETER;
    case NAMED_ARGUMENT;
}
