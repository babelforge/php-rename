<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Domain\Rename\Diagnostic;

/**
 * Enumerates rename diagnostic severities.
 */
enum RenameDiagnosticSeverity
{
    case INFO;
    case WARNING;
    case ERROR;
}
