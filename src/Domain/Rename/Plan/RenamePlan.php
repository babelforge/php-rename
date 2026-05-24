<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Domain\Rename\Plan;

use BabelForge\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticCollection;
use BabelForge\PhpRename\Domain\Rename\Operation\RenameOperationCollection;
use BabelForge\PhpRename\Domain\Rename\Request\RenameRequestInterface;

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
