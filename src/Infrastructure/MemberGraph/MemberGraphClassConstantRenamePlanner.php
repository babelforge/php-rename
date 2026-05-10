<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Infrastructure\MemberGraph;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\MemberGraph\Application\Source\Node\MemberGraphSourceNodeLocator;
use PhpNoobs\MemberGraph\Application\Source\Node\VirtualPhpSourceFileNodeMatchRole;
use PhpNoobs\PhpRename\Application\Contract\ClassConstantRenamePlannerInterface;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnostic;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticCollection;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticSeverity;
use PhpNoobs\PhpRename\Domain\Rename\Operation\RenameOperation;
use PhpNoobs\PhpRename\Domain\Rename\Operation\RenameOperationCollection;
use PhpNoobs\PhpRename\Domain\Rename\Operation\RenameOperationRole;
use PhpNoobs\PhpRename\Domain\Rename\Plan\RenamePlan;
use PhpNoobs\PhpRename\Domain\Rename\Request\ClassConstantRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\Symbol\RenameSymbolKind;

/**
 * Plans class-constant renames from `member-graph` semantic facts.
 */
final readonly class MemberGraphClassConstantRenamePlanner implements ClassConstantRenamePlannerInterface
{
    /**
     * Plans a class-constant rename.
     *
     * @param ClassConstantRenameRequest $request the class-constant rename request
     * @param MemberDependencyGraphBuild $build   the member graph build used to resolve declarations and usages
     */
    public function plan(ClassConstantRenameRequest $request, MemberDependencyGraphBuild $build): RenamePlan
    {
        $diagnostics = RenameDiagnosticCollection::empty();
        $operations = RenameOperationCollection::empty();
        new MemberGraphRenameConflictGuard()->reportClassConstantConflicts($diagnostics, $request, $build);
        $matches = MemberGraphSourceNodeLocator::fromBuild($build)
            ->classConstant($request->className, $request->constantName);

        foreach ($matches as $match) {
            $operationRole = $this->operationRole($match->role);

            if (null === $operationRole) {
                $diagnostics->add(new RenameDiagnostic(
                    severity: RenameDiagnosticSeverity::WARNING,
                    message: 'Unsupported class-constant rename source-node match role.',
                ));

                continue;
            }

            $operations->add(new RenameOperation(
                symbolKind: RenameSymbolKind::CLASS_CONSTANT,
                role: $operationRole,
                file: $match->virtualFile,
                node: $match->node,
                oldName: $request->constantName,
                newName: $request->newConstantName,
            ));
        }

        if (0 === count($operations)) {
            $diagnostics->add(new RenameDiagnostic(
                severity: RenameDiagnosticSeverity::WARNING,
                message: 'No source-node match was found for the requested class-constant rename.',
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
