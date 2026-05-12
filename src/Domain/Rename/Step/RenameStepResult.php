<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Domain\Rename\Step;

use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticCollection;
use PhpNoobs\PhpRename\Domain\Rename\Plan\RenamePlan;
use PhpNoobs\PhpRename\Domain\Rename\Plan\RenameResult;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;

/**
 * Describes the result of one orchestrable rename step.
 */
final readonly class RenameStepResult
{
    /**
     * Constructor.
     *
     * @param RenameStepContext              $context      the post-step context
     * @param RenamePlan                     $plan         the planned rename
     * @param RenameResult                   $renameResult the low-level rename result
     * @param RenameDiagnosticCollection     $diagnostics  the aggregate step diagnostics
     * @param VirtualPhpSourceFileCollection $touchedFiles the files touched by the step plan
     * @param bool                           $applied      whether the step was applied without blocking diagnostics
     */
    public function __construct(
        public RenameStepContext $context,
        public RenamePlan $plan,
        public RenameResult $renameResult,
        public RenameDiagnosticCollection $diagnostics,
        public VirtualPhpSourceFileCollection $touchedFiles,
        public bool $applied,
    ) {
    }
}
