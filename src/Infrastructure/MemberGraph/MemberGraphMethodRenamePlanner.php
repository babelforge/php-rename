<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Infrastructure\MemberGraph;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\MemberGraph\Application\Source\Node\MemberGraphSourceNodeLocator;
use PhpNoobs\MemberGraph\Application\Source\Node\VirtualPhpSourceFileNodeMatchRole;
use PhpNoobs\PhpRename\Application\Contract\MethodRenamePlannerInterface;
use PhpNoobs\PhpRename\Domain\Rename\MethodRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\RenameDiagnostic;
use PhpNoobs\PhpRename\Domain\Rename\RenameDiagnosticCollection;
use PhpNoobs\PhpRename\Domain\Rename\RenameDiagnosticSeverity;
use PhpNoobs\PhpRename\Domain\Rename\RenameOperation;
use PhpNoobs\PhpRename\Domain\Rename\RenameOperationCollection;
use PhpNoobs\PhpRename\Domain\Rename\RenameOperationRole;
use PhpNoobs\PhpRename\Domain\Rename\RenamePlan;
use PhpNoobs\PhpRename\Domain\Rename\RenameSymbolKind;

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
        $diagnostics = RenameDiagnosticCollection::empty();
        $operations = RenameOperationCollection::empty();
        $matches = MemberGraphSourceNodeLocator::fromBuild($build)
            ->method($request->className, $request->methodName);

        foreach ($matches as $match) {
            $operationRole = $this->operationRole($match->role);

            if (null === $operationRole) {
                $diagnostics->add(new RenameDiagnostic(
                    severity: RenameDiagnosticSeverity::WARNING,
                    message: 'Unsupported method rename source-node match role.',
                ));

                continue;
            }

            $operations->add(new RenameOperation(
                symbolKind: RenameSymbolKind::METHOD,
                role: $operationRole,
                file: $match->virtualFile,
                node: $match->node,
                oldName: $request->methodName,
                newName: $request->newMethodName,
            ));
        }

        if (0 === count($operations)) {
            $diagnostics->add(new RenameDiagnostic(
                severity: RenameDiagnosticSeverity::WARNING,
                message: 'No source-node match was found for the requested method rename.',
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
