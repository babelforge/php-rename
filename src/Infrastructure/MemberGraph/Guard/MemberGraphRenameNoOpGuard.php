<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Infrastructure\MemberGraph\Guard;

use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnostic;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticCollection;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticSeverity;
use PhpNoobs\PhpRename\Domain\Rename\Request\RenameRequestInterface;

/**
 * Reports rename requests that would not change the symbol name.
 */
final readonly class MemberGraphRenameNoOpGuard
{
    /**
     * Reports a no-op rename request.
     *
     * @param RenameDiagnosticCollection $diagnostics the diagnostics to update
     * @param RenameRequestInterface     $request     the rename request
     */
    public function reportNoOp(RenameDiagnosticCollection $diagnostics, RenameRequestInterface $request): bool
    {
        if ($request->oldName() !== $request->newName()) {
            return false;
        }

        $diagnostics->add(new RenameDiagnostic(
            severity: RenameDiagnosticSeverity::WARNING,
            message: sprintf('The requested rename is a no-op because "%s" is already the current name.', $request->newName()),
        ));

        return true;
    }
}
