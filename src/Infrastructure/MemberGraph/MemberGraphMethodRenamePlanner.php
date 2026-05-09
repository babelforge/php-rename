<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Infrastructure\MemberGraph;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\PhpRename\Application\Contract\MethodRenamePlannerInterface;
use PhpNoobs\PhpRename\Domain\Rename\MethodRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\RenameDiagnostic;
use PhpNoobs\PhpRename\Domain\Rename\RenameDiagnosticCollection;
use PhpNoobs\PhpRename\Domain\Rename\RenameDiagnosticSeverity;
use PhpNoobs\PhpRename\Domain\Rename\RenameOperationCollection;
use PhpNoobs\PhpRename\Domain\Rename\RenamePlan;

/**
 * Plans method renames from `member-graph` semantic facts.
 */
final readonly class MemberGraphMethodRenamePlanner implements MethodRenamePlannerInterface
{
    /**
     * Plans a method rename.
     *
     * @param MethodRenameRequest        $request the method rename request
     * @param MemberDependencyGraphBuild $build   the member graph build used to resolve declarations and usages
     */
    public function plan(MethodRenameRequest $request, MemberDependencyGraphBuild $build): RenamePlan
    {
        $diagnostics = RenameDiagnosticCollection::empty()
            ->add(new RenameDiagnostic(
                severity: RenameDiagnosticSeverity::INFO,
                message: 'Semantic method rename planning is not implemented yet.',
            ));

        return new RenamePlan(
            request: $request,
            operations: RenameOperationCollection::empty(),
            diagnostics: $diagnostics,
        );
    }
}
