<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Infrastructure\MemberGraph\Planner;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use BabelForge\MemberGraph\Application\Source\Node\MemberGraphSourceNodeLocator;
use BabelForge\MemberGraph\Application\Source\Node\VirtualPhpSourceFileNodeMatchRole;
use BabelForge\PhpRename\Application\Contract\ClassFqcnRenamePlannerInterface;
use BabelForge\PhpRename\Domain\Rename\Diagnostic\RenameDiagnostic;
use BabelForge\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticCollection;
use BabelForge\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticSeverity;
use BabelForge\PhpRename\Domain\Rename\Operation\RenameOperation;
use BabelForge\PhpRename\Domain\Rename\Operation\RenameOperationCollection;
use BabelForge\PhpRename\Domain\Rename\Operation\RenameOperationRole;
use BabelForge\PhpRename\Domain\Rename\Plan\RenamePlan;
use BabelForge\PhpRename\Domain\Rename\Request\ClassFqcnRenameRequest;
use BabelForge\PhpRename\Domain\Rename\Symbol\RenameSymbolKind;
use BabelForge\PhpRename\Infrastructure\MemberGraph\Guard\MemberGraphRenameConflictGuard;
use BabelForge\PhpRename\Infrastructure\MemberGraph\Guard\MemberGraphRenameNoOpGuard;

/**
 * Plans fully-qualified class-like owner renames from `member-graph` semantic facts.
 */
final readonly class MemberGraphClassFqcnRenamePlanner implements ClassFqcnRenamePlannerInterface
{
    /**
     * Plans a fully-qualified class-like owner rename.
     *
     * @param ClassFqcnRenameRequest     $request the class FQCN rename request
     * @param MemberDependencyGraphBuild $build   the member graph build used to resolve declarations and usages
     */
    public function plan(ClassFqcnRenameRequest $request, MemberDependencyGraphBuild $build): RenamePlan
    {
        $diagnostics = RenameDiagnosticCollection::empty();
        $operations = RenameOperationCollection::empty();

        if (new MemberGraphRenameNoOpGuard()->reportNoOp($diagnostics, $request)) {
            return new RenamePlan($request, $operations, $diagnostics);
        }

        new MemberGraphRenameConflictGuard()->reportClassFqcnConflicts($diagnostics, $request, $build);
        $matches = MemberGraphSourceNodeLocator::fromBuild($build)
            ->owner($request->className);

        foreach ($matches as $match) {
            $operationRole = $this->operationRole($match->role);

            if (null === $operationRole) {
                $diagnostics->add(new RenameDiagnostic(
                    severity: RenameDiagnosticSeverity::WARNING,
                    message: 'Unsupported class FQCN rename source-node match role.',
                ));

                continue;
            }

            $operations->add(new RenameOperation(
                symbolKind: RenameSymbolKind::CLASS_,
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
                message: 'No source-node match was found for the requested class FQCN rename.',
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
            VirtualPhpSourceFileNodeMatchRole::OWNER_DECLARATION => RenameOperationRole::DECLARATION,
            VirtualPhpSourceFileNodeMatchRole::OWNER_USAGE => RenameOperationRole::USAGE,
            default => null,
        };
    }
}
