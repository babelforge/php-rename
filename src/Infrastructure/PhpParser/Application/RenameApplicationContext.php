<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Infrastructure\PhpParser\Application;

use BabelForge\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticCollection;

/**
 * Carries shared state while applying rename operations.
 */
final readonly class RenameApplicationContext
{
    /**
     * Constructor.
     *
     * @param RenameDiagnosticCollection $diagnostics the diagnostics collected during rename application
     */
    public function __construct(
        public RenameDiagnosticCollection $diagnostics,
    ) {
    }
}
