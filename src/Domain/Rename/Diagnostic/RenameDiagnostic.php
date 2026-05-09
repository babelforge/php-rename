<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Domain\Rename\Diagnostic;

/**
 * Describes a rename planning or application diagnostic.
 */
final readonly class RenameDiagnostic
{
    /**
     * Constructor.
     *
     * @param RenameDiagnosticSeverity $severity the diagnostic severity
     * @param string                   $message  the diagnostic message
     */
    public function __construct(
        public RenameDiagnosticSeverity $severity,
        public string $message,
    ) {
    }
}
