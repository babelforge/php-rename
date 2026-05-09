<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Domain\Rename;

/**
 * Enumerates rename diagnostic severities.
 */
enum RenameDiagnosticSeverity
{
    case INFO;
    case WARNING;
    case ERROR;
}
