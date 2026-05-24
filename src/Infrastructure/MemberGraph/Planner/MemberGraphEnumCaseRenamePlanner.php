<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Infrastructure\MemberGraph\Planner;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use BabelForge\MemberGraph\Application\Source\Node\MemberGraphSourceNodeLocator;
use BabelForge\MemberGraph\Application\Source\Node\VirtualPhpSourceFileNodeMatchRole;
use BabelForge\PhpRename\Application\Contract\EnumCaseRenamePlannerInterface;
use BabelForge\PhpRename\Domain\Rename\Diagnostic\RenameDiagnostic;
use BabelForge\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticCollection;
use BabelForge\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticSeverity;
use BabelForge\PhpRename\Domain\Rename\Operation\RenameOperation;
use BabelForge\PhpRename\Domain\Rename\Operation\RenameOperationCollection;
use BabelForge\PhpRename\Domain\Rename\Operation\RenameOperationRole;
use BabelForge\PhpRename\Domain\Rename\Plan\RenamePlan;
use BabelForge\PhpRename\Domain\Rename\Request\EnumCaseRenameRequest;
use BabelForge\PhpRename\Domain\Rename\Symbol\RenameSymbolKind;
use BabelForge\PhpRename\Infrastructure\MemberGraph\Guard\MemberGraphRenameConflictGuard;
use BabelForge\PhpRename\Infrastructure\MemberGraph\Guard\MemberGraphRenameNoOpGuard;

/**
 * Plans enum-case renames from `member-graph` semantic facts.
 */
final readonly class MemberGraphEnumCaseRenamePlanner implements EnumCaseRenamePlannerInterface
{
    /**
     * Plans an enum-case rename.
     *
     * @param EnumCaseRenameRequest      $request the enum-case rename request
     * @param MemberDependencyGraphBuild $build   the member graph build used to resolve declarations and usages
     */
    public function plan(EnumCaseRenameRequest $request, MemberDependencyGraphBuild $build): RenamePlan
    {
        $diagnostics = RenameDiagnosticCollection::empty();
        $operations = RenameOperationCollection::empty();

        if (new MemberGraphRenameNoOpGuard()->reportNoOp($diagnostics, $request)) {
            return new RenamePlan($request, $operations, $diagnostics);
        }

        new MemberGraphRenameConflictGuard()->reportEnumCaseConflicts($diagnostics, $request, $build);
        $matches = MemberGraphSourceNodeLocator::fromBuild($build)
            ->classConstant($request->enumName, $request->caseName);

        foreach ($matches as $match) {
            $operationRole = $this->operationRole($match->role);

            if (null === $operationRole) {
                $diagnostics->add(new RenameDiagnostic(
                    severity: RenameDiagnosticSeverity::WARNING,
                    message: 'Unsupported enum-case rename source-node match role.',
                ));

                continue;
            }

            $operations->add(new RenameOperation(
                symbolKind: RenameSymbolKind::ENUM_CASE,
                role: $operationRole,
                file: $match->virtualFile,
                node: $match->node,
                oldName: $request->caseName,
                newName: $request->newCaseName,
            ));
        }

        if (0 === count($operations)) {
            $diagnostics->add(new RenameDiagnostic(
                severity: RenameDiagnosticSeverity::WARNING,
                message: 'No source-node match was found for the requested enum-case rename.',
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
