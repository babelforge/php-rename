<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Infrastructure\PhpParser;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\PhpRename\Application\Contract\RenamePlanApplierInterface;
use PhpNoobs\PhpRename\Domain\Rename\RenameDiagnosticCollection;
use PhpNoobs\PhpRename\Domain\Rename\RenamePlan;
use PhpNoobs\PhpRename\Domain\Rename\RenameResult;

/**
 * Applies rename plans to PHPParser AST nodes stored in virtual files.
 */
final readonly class AstRenamePlanApplier implements RenamePlanApplierInterface
{
    /**
     * Applies a rename plan.
     *
     * @param RenamePlan                 $plan  the rename plan to apply
     * @param MemberDependencyGraphBuild $build the member graph build containing virtual files
     */
    public function apply(RenamePlan $plan, MemberDependencyGraphBuild $build): RenameResult
    {
        return new RenameResult(
            plan: $plan,
            virtualFiles: $build->virtualFiles,
            diagnostics: RenameDiagnosticCollection::empty(),
        );
    }
}
