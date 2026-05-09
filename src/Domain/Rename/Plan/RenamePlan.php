<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Domain\Rename\Plan;

use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticCollection;
use PhpNoobs\PhpRename\Domain\Rename\Operation\RenameOperationCollection;
use PhpNoobs\PhpRename\Domain\Rename\Request\RenameRequestInterface;

/**
 * Describes all AST operations and diagnostics for a rename request.
 */
final readonly class RenamePlan
{
    /**
     * Constructor.
     *
     * @param RenameRequestInterface     $request     the rename request
     * @param RenameOperationCollection  $operations  the AST rename operations
     * @param RenameDiagnosticCollection $diagnostics the planning diagnostics
     */
    public function __construct(
        public RenameRequestInterface $request,
        public RenameOperationCollection $operations,
        public RenameDiagnosticCollection $diagnostics,
    ) {
    }
}
