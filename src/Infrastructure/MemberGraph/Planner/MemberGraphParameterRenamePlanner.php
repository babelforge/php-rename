<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Infrastructure\MemberGraph\Planner;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\MemberGraph\Application\Source\Node\MemberGraphSourceNodeLocator;
use PhpNoobs\MemberGraph\Application\Source\Node\VirtualPhpSourceFileNodeMatchRole;
use PhpNoobs\PhpRename\Application\Contract\ParameterRenamePlannerInterface;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnostic;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticCollection;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticSeverity;
use PhpNoobs\PhpRename\Domain\Rename\Operation\RenameOperation;
use PhpNoobs\PhpRename\Domain\Rename\Operation\RenameOperationCollection;
use PhpNoobs\PhpRename\Domain\Rename\Operation\RenameOperationRole;
use PhpNoobs\PhpRename\Domain\Rename\Plan\RenamePlan;
use PhpNoobs\PhpRename\Domain\Rename\Request\ParameterRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\Symbol\RenameSymbolKind;
use PhpNoobs\PhpRename\Infrastructure\MemberGraph\Guard\MemberGraphRenameConflictGuard;
use PhpNoobs\PhpRename\Infrastructure\MemberGraph\Guard\MemberGraphRenameNoOpGuard;

/**
 * Plans parameter renames from `member-graph` semantic facts.
 */
final readonly class MemberGraphParameterRenamePlanner implements ParameterRenamePlannerInterface
{
    /**
     * Plans a parameter rename.
     *
     * @param ParameterRenameRequest     $request the parameter rename request
     * @param MemberDependencyGraphBuild $build   the member graph build used to resolve declarations and usages
     */
    public function plan(ParameterRenameRequest $request, MemberDependencyGraphBuild $build): RenamePlan
    {
        $diagnostics = RenameDiagnosticCollection::empty();
        $operations = RenameOperationCollection::empty();

        if (new MemberGraphRenameNoOpGuard()->reportNoOp($diagnostics, $request)) {
            return new RenamePlan($request, $operations, $diagnostics);
        }

        new MemberGraphRenameConflictGuard()->reportParameterConflicts($diagnostics, $request, $build);
        $matches = MemberGraphSourceNodeLocator::fromBuild($build)
            ->parameter($request->owner, $request->functionLikeName, $request->parameterName, $request->parameterIndex);

        foreach ($matches as $match) {
            $operationRole = $this->operationRole($match->role);

            if (null === $operationRole) {
                $diagnostics->add(new RenameDiagnostic(
                    severity: RenameDiagnosticSeverity::WARNING,
                    message: 'Unsupported parameter rename source-node match role.',
                ));

                continue;
            }

            $operations->add(new RenameOperation(
                symbolKind: RenameSymbolKind::PARAMETER,
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
                message: 'No source-node match was found for the requested parameter rename.',
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
            VirtualPhpSourceFileNodeMatchRole::PARAMETER_DECLARATION => RenameOperationRole::DECLARATION,
            VirtualPhpSourceFileNodeMatchRole::PARAMETER_USAGE => RenameOperationRole::USAGE,
            VirtualPhpSourceFileNodeMatchRole::PARAMETER_LOCAL_USAGE => RenameOperationRole::USAGE,
            default => null,
        };
    }
}
