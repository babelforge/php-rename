<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Domain\Rename\Symbol;

/**
 * Enumerates supported rename symbol kinds.
 */
enum RenameSymbolKind
{
    case CLASS_;
    case FUNCTION_;
    case CONSTANT;
    case METHOD;
    case PROPERTY;
    case CLASS_CONSTANT;
    case ENUM_CASE;
    case PARAMETER;
    case NAMED_ARGUMENT;
}
