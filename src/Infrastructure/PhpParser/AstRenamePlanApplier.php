<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Infrastructure\PhpParser;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\PhpRename\Application\Contract\RenamePlanApplierInterface;
use PhpNoobs\PhpRename\Domain\Rename\RenameDiagnostic;
use PhpNoobs\PhpRename\Domain\Rename\RenameDiagnosticCollection;
use PhpNoobs\PhpRename\Domain\Rename\RenameDiagnosticSeverity;
use PhpNoobs\PhpRename\Domain\Rename\RenameOperation;
use PhpNoobs\PhpRename\Domain\Rename\RenamePlan;
use PhpNoobs\PhpRename\Domain\Rename\RenameResult;
use PhpNoobs\PhpRename\Domain\Rename\RenameSymbolKind;
use PhpNoobs\PhpSource\VirtualPhpSourceFile;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\ClassMethod;

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
        $diagnostics = RenameDiagnosticCollection::empty();
        /** @var array<string, VirtualPhpSourceFile> $updatedVirtualFiles */
        $updatedVirtualFiles = [];

        foreach ($plan->operations as $operation) {
            if (!$this->applyOperation($operation, $diagnostics)) {
                continue;
            }

            $updatedVirtualFiles[$operation->file->virtualFilePath] = $operation->file;
        }

        foreach ($updatedVirtualFiles as $virtualFile) {
            $virtualFile->update($virtualFile->nodes);
        }

        return new RenameResult(
            plan: $plan,
            virtualFiles: $build->virtualFiles,
            diagnostics: $diagnostics,
        );
    }

    /**
     * Applies one rename operation.
     *
     * @param RenameOperation            $operation   the rename operation
     * @param RenameDiagnosticCollection $diagnostics the diagnostics to update
     */
    private function applyOperation(RenameOperation $operation, RenameDiagnosticCollection $diagnostics): bool
    {
        if (RenameSymbolKind::METHOD !== $operation->symbolKind) {
            $diagnostics->add(new RenameDiagnostic(
                severity: RenameDiagnosticSeverity::WARNING,
                message: 'Unsupported rename symbol kind.',
            ));

            return false;
        }

        return $this->renameMethodNode($operation, $diagnostics);
    }

    /**
     * Renames one method declaration or usage node.
     *
     * @param RenameOperation            $operation   the method rename operation
     * @param RenameDiagnosticCollection $diagnostics the diagnostics to update
     */
    private function renameMethodNode(RenameOperation $operation, RenameDiagnosticCollection $diagnostics): bool
    {
        $node = $operation->node;

        if ($node instanceof ClassMethod) {
            $node->name = $this->replacementIdentifier($node->name, $operation->newName);

            return true;
        }

        if (
            ($node instanceof MethodCall || $node instanceof NullsafeMethodCall || $node instanceof StaticCall)
            && $node->name instanceof Identifier
        ) {
            $node->name = $this->replacementIdentifier($node->name, $operation->newName);

            return true;
        }

        $diagnostics->add(new RenameDiagnostic(
            severity: RenameDiagnosticSeverity::WARNING,
            message: sprintf('Unsupported method rename node "%s".', $node::class),
        ));

        return false;
    }

    /**
     * Creates a replacement identifier while preserving node attributes.
     *
     * @param Identifier $identifier the original identifier
     * @param string     $name       the replacement name
     */
    private function replacementIdentifier(Identifier $identifier, string $name): Identifier
    {
        return new Identifier($name, $identifier->getAttributes());
    }
}
