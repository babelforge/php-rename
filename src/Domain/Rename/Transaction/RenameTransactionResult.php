<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Domain\Rename\Transaction;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use BabelForge\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticCollection;
use BabelForge\PhpRename\Domain\Rename\Plan\RenameResult;
use BabelForge\PhpSource\VirtualPhpSourceFileCollection;

/**
 * Describes the aggregate result of a rename transaction.
 */
final readonly class RenameTransactionResult
{
    /**
     * Constructor.
     *
     * @param RenameTransactionStatus        $status        the transaction status
     * @param list<RenameResult>             $actionResults the individual rename action results
     * @param MemberDependencyGraphBuild     $finalBuild    the final member graph build
     * @param VirtualPhpSourceFileCollection $virtualFiles  the final virtual files
     * @param RenameDiagnosticCollection     $diagnostics   the aggregate diagnostics
     */
    public function __construct(
        public RenameTransactionStatus $status,
        public array $actionResults,
        public MemberDependencyGraphBuild $finalBuild,
        public VirtualPhpSourceFileCollection $virtualFiles,
        public RenameDiagnosticCollection $diagnostics,
    ) {
    }
}
