<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Infrastructure\MemberGraph;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\MemberGraph\Application\Source\Node\MemberGraphSourceNodeLocator;
use PhpNoobs\MemberGraph\Application\Source\Node\VirtualPhpSourceFileNodeMatchRole;
use PhpNoobs\PhpRename\Application\Contract\PropertyRenamePlannerInterface;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnostic;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticCollection;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticSeverity;
use PhpNoobs\PhpRename\Domain\Rename\Operation\RenameOperation;
use PhpNoobs\PhpRename\Domain\Rename\Operation\RenameOperationCollection;
use PhpNoobs\PhpRename\Domain\Rename\Operation\RenameOperationRole;
use PhpNoobs\PhpRename\Domain\Rename\Plan\RenamePlan;
use PhpNoobs\PhpRename\Domain\Rename\Request\PropertyRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\Symbol\RenameSymbolKind;

/**
 * Plans property renames from `member-graph` semantic facts.
 */
final readonly class MemberGraphPropertyRenamePlanner implements PropertyRenamePlannerInterface
{
    /**
     * Plans a property rename.
     *
     * @param PropertyRenameRequest      $request the property rename request
     * @param MemberDependencyGraphBuild $build   the member graph build used to resolve declarations and usages
     */
    public function plan(PropertyRenameRequest $request, MemberDependencyGraphBuild $build): RenamePlan
    {
        $diagnostics = RenameDiagnosticCollection::empty();
        $operations = RenameOperationCollection::empty();
        $matches = MemberGraphSourceNodeLocator::fromBuild($build)
            ->property($request->className, $request->propertyName);

        foreach ($matches as $match) {
            $operationRole = $this->operationRole($match->role);

            if (null === $operationRole) {
                $diagnostics->add(new RenameDiagnostic(
                    severity: RenameDiagnosticSeverity::WARNING,
                    message: 'Unsupported property rename source-node match role.',
                ));

                continue;
            }

            $operations->add(new RenameOperation(
                symbolKind: RenameSymbolKind::PROPERTY,
                role: $operationRole,
                file: $match->virtualFile,
                node: $match->node,
                oldName: $request->propertyName,
                newName: $request->newPropertyName,
            ));
        }

        if (0 === count($operations)) {
            $diagnostics->add(new RenameDiagnostic(
                severity: RenameDiagnosticSeverity::WARNING,
                message: 'No source-node match was found for the requested property rename.',
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
