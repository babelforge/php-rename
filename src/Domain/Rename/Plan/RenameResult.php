<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Domain\Rename\Plan;

use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticCollection;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;

/**
 * Describes the outcome of applying a rename plan.
 */
final readonly class RenameResult
{
    /**
     * Constructor.
     *
     * @param RenamePlan                     $plan         the applied rename plan
     * @param VirtualPhpSourceFileCollection $virtualFiles the virtual files after AST mutation
     * @param RenameDiagnosticCollection     $diagnostics  the application diagnostics
     */
    public function __construct(
        public RenamePlan $plan,
        public VirtualPhpSourceFileCollection $virtualFiles,
        public RenameDiagnosticCollection $diagnostics,
    ) {
    }
}
