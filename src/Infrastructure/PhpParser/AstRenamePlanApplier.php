<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Infrastructure\PhpParser;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\PhpRename\Application\Contract\RenamePlanApplierInterface;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnostic;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticCollection;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticSeverity;
use PhpNoobs\PhpRename\Domain\Rename\Operation\RenameOperation;
use PhpNoobs\PhpRename\Domain\Rename\Plan\RenamePlan;
use PhpNoobs\PhpRename\Domain\Rename\Plan\RenameResult;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Application\RenameApplicationContext;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Application\RenameMetadataApplierInterface;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Application\RenameNodeApplierInterface;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Class_\ClassRenameNodeApplier;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\ClassConstant\ClassConstantRenameNodeApplier;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Constant\ConstantRenameNodeApplier;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Docblock\ClassConstantDocblockRenameApplier;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Docblock\ClassDocblockRenameApplier;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Docblock\FunctionDocblockRenameApplier;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Docblock\MethodDocblockRenameApplier;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Docblock\ParameterDocblockRenameApplier;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Docblock\PropertyDocblockRenameApplier;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Function_\FunctionRenameNodeApplier;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Method\MethodRenameNodeApplier;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Parameter\ParameterRenameNodeApplier;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Property\PropertyRenameNodeApplier;
use PhpNoobs\PhpSource\VirtualPhpSourceFile;

/**
 * Applies rename plans to PHPParser AST nodes stored in virtual files.
 */
final readonly class AstRenamePlanApplier implements RenamePlanApplierInterface
{
    /**
     * @var list<RenameNodeApplierInterface>
     */
    private array $nodeAppliers;

    /**
     * @var list<RenameMetadataApplierInterface>
     */
    private array $metadataAppliers;

    /**
     * Constructor.
     *
     * @param list<RenameNodeApplierInterface>|null     $nodeAppliers     the optional node appliers
     * @param list<RenameMetadataApplierInterface>|null $metadataAppliers the optional metadata appliers
     */
    public function __construct(
        ?array $nodeAppliers = null,
        ?array $metadataAppliers = null,
    ) {
        $this->nodeAppliers = $nodeAppliers ?? [
            new ClassRenameNodeApplier(),
            new ConstantRenameNodeApplier(),
            new MethodRenameNodeApplier(),
            new PropertyRenameNodeApplier(),
            new ClassConstantRenameNodeApplier(),
            new FunctionRenameNodeApplier(),
            new ParameterRenameNodeApplier(),
        ];
        $this->metadataAppliers = $metadataAppliers ?? [
            new ClassDocblockRenameApplier(),
            new MethodDocblockRenameApplier(),
            new PropertyDocblockRenameApplier(),
            new ClassConstantDocblockRenameApplier(),
            new FunctionDocblockRenameApplier(),
            new ParameterDocblockRenameApplier(),
        ];
    }

    /**
     * Applies a rename plan.
     *
     * @param RenamePlan                 $plan  the rename plan to apply
     * @param MemberDependencyGraphBuild $build the member graph build containing virtual files
     */
    public function apply(RenamePlan $plan, MemberDependencyGraphBuild $build): RenameResult
    {
        $diagnostics = RenameDiagnosticCollection::empty();

        if ($plan->diagnostics->hasErrors()) {
            return new RenameResult(
                plan: $plan,
                virtualFiles: $build->virtualFiles,
                diagnostics: $diagnostics,
            );
        }

        $context = new RenameApplicationContext($diagnostics);
        /** @var array<string, VirtualPhpSourceFile> $updatedVirtualFiles */
        $updatedVirtualFiles = [];

        foreach ($plan->operations as $operation) {
            if (!$this->applyOperation($operation, $context)) {
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
     * @param RenameOperation          $operation the rename operation
     * @param RenameApplicationContext $context   the rename application context
     */
    private function applyOperation(RenameOperation $operation, RenameApplicationContext $context): bool
    {
        foreach ($this->nodeAppliers as $nodeApplier) {
            if (!$nodeApplier->supports($operation)) {
                continue;
            }

            if (!$nodeApplier->apply($operation, $context)) {
                return false;
            }

            $this->applyMetadata($operation, $context);

            return true;
        }

        $context->diagnostics->add(new RenameDiagnostic(
            severity: RenameDiagnosticSeverity::WARNING,
            message: 'Unsupported rename symbol kind.',
        ));

        return false;
    }

    /**
     * Applies metadata mutations associated with one rename operation.
     *
     * @param RenameOperation          $operation the rename operation
     * @param RenameApplicationContext $context   the rename application context
     */
    private function applyMetadata(RenameOperation $operation, RenameApplicationContext $context): void
    {
        foreach ($this->metadataAppliers as $metadataApplier) {
            if ($metadataApplier->supports($operation)) {
                $metadataApplier->apply($operation, $context);
            }
        }
    }
}
