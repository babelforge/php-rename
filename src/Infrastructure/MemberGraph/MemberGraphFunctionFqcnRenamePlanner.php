<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Infrastructure\MemberGraph;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\MemberGraph\Application\Source\Node\MemberGraphSourceNodeLocator;
use PhpNoobs\MemberGraph\Application\Source\Node\VirtualPhpSourceFileNodeMatchRole;
use PhpNoobs\PhpRename\Application\Contract\FunctionFqcnRenamePlannerInterface;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnostic;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticCollection;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticSeverity;
use PhpNoobs\PhpRename\Domain\Rename\Operation\RenameOperation;
use PhpNoobs\PhpRename\Domain\Rename\Operation\RenameOperationCollection;
use PhpNoobs\PhpRename\Domain\Rename\Operation\RenameOperationRole;
use PhpNoobs\PhpRename\Domain\Rename\Plan\RenamePlan;
use PhpNoobs\PhpRename\Domain\Rename\Request\FunctionFqcnRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\Symbol\RenameSymbolKind;

/**
 * Plans fully-qualified function renames from `member-graph` semantic facts.
 */
final readonly class MemberGraphFunctionFqcnRenamePlanner implements FunctionFqcnRenamePlannerInterface
{
    /**
     * Plans a fully-qualified function rename.
     *
     * @param FunctionFqcnRenameRequest  $request the function FQCN rename request
     * @param MemberDependencyGraphBuild $build   the member graph build used to resolve declarations and usages
     */
    public function plan(FunctionFqcnRenameRequest $request, MemberDependencyGraphBuild $build): RenamePlan
    {
        $diagnostics = RenameDiagnosticCollection::empty();
        $operations = RenameOperationCollection::empty();
        new MemberGraphRenameConflictGuard()->reportFunctionFqcnConflicts($diagnostics, $request, $build);
        $matches = MemberGraphSourceNodeLocator::fromBuild($build)
            ->function($request->functionName);

        foreach ($matches as $match) {
            $operationRole = $this->operationRole($match->role);

            if (null === $operationRole) {
                $diagnostics->add(new RenameDiagnostic(
                    severity: RenameDiagnosticSeverity::WARNING,
                    message: 'Unsupported function FQCN rename source-node match role.',
                ));

                continue;
            }

            $operations->add(new RenameOperation(
                symbolKind: RenameSymbolKind::FUNCTION_,
                role: $operationRole,
                file: $match->virtualFile,
                node: $match->node,
                oldName: $request->oldName(),
                newName: $request->newName(),
            ));
        }

        if (0 === count($operations)) {
            $diagnostics->add(new RenameDiagnostic(
                severity: RenameDiagnosticSeverity::WARNING,
                message: 'No source-node match was found for the requested function FQCN rename.',
            ));
        }

        return new RenamePlan(
            request: $request,
            operations: $operations,
            diagnostics: $diagnostics,
        );
    }

    /**
     * Converts a source-node match role to a rename operation role.
     *
     * @param VirtualPhpSourceFileNodeMatchRole $role the source-node match role
     */
    private function operationRole(VirtualPhpSourceFileNodeMatchRole $role): ?RenameOperationRole
    {
        return match ($role) {
            VirtualPhpSourceFileNodeMatchRole::MEMBER_DECLARATION => RenameOperationRole::DECLARATION,
            VirtualPhpSourceFileNodeMatchRole::MEMBER_USAGE => RenameOperationRole::USAGE,
            default => null,
        };
    }
}
