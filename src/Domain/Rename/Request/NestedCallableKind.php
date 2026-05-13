<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Domain\Rename\Request;

/**
 * Identifies the nested callable kind to rename within.
 */
enum NestedCallableKind
{
    case CLOSURE;
    case ARROW_FUNCTION;
}
