<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Infrastructure\PhpParser\Application;

use PhpNoobs\PhpRename\Domain\Rename\RenameDiagnosticCollection;

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
