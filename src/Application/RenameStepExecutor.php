<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Application;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphFactory;
use BabelForge\MemberGraph\Application\Build\Projection\MemberGraphBuildOverlay;
use BabelForge\MemberGraph\Application\Build\Projection\MemberGraphProjectedBuildFactory;
use BabelForge\PhpRename\Application\Contract\RenamePlanApplierInterface;
use BabelForge\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticCollection;
use BabelForge\PhpRename\Domain\Rename\Plan\RenamePlan;
use BabelForge\PhpRename\Domain\Rename\Plan\RenameResult;
use BabelForge\PhpRename\Domain\Rename\Request\ClassConstantRenameRequest;
use BabelForge\PhpRename\Domain\Rename\Request\ClassFqcnRenameRequest;
use BabelForge\PhpRename\Domain\Rename\Request\ClassRenameRequest;
use BabelForge\PhpRename\Domain\Rename\Request\ConstantFqcnRenameRequest;
use BabelForge\PhpRename\Domain\Rename\Request\ConstantRenameRequest;
use BabelForge\PhpRename\Domain\Rename\Request\EnumCaseRenameRequest;
use BabelForge\PhpRename\Domain\Rename\Request\FunctionFqcnRenameRequest;
use BabelForge\PhpRename\Domain\Rename\Request\FunctionRenameRequest;
use BabelForge\PhpRename\Domain\Rename\Request\MethodRenameRequest;
use BabelForge\PhpRename\Domain\Rename\Request\ParameterRenameRequest;
use BabelForge\PhpRename\Domain\Rename\Request\PropertyRenameRequest;
use BabelForge\PhpRename\Domain\Rename\Request\RenameRequestInterface;
use BabelForge\PhpRename\Domain\Rename\Step\RenameStepContext;
use BabelForge\PhpRename\Domain\Rename\Step\RenameStepResult;
use BabelForge\PhpSource\VirtualPhpSourceFileCollection;

/**
 * Executes one rename plan against an orchestrable step context.
 */
final readonly class RenameStepExecutor
{
    /**
     * Constructor.
     *
     * @param RenamePlanApplierInterface $renamePlanApplier the rename plan applier
     */
    public function __construct(private RenamePlanApplierInterface $renamePlanApplier)
    {
    }

    /**
     * Executes one planned rename step.
     *
     * @param RenamePlan        $plan    the rename plan to execute
     * @param RenameStepContext $context the current step context
     */
    public function execute(RenamePlan $plan, RenameStepContext $context): RenameStepResult
    {
        $diagnostics = RenameDiagnosticCollection::empty();
        $this->mergeDiagnostics($diagnostics, $plan->diagnostics);
        $touchedFiles = $this->touchedFiles($plan);

        if ($plan->diagnostics->hasErrors()) {
            return new RenameStepResult(
                context: $context,
                plan: $plan,
                renameResult: new RenameResult($plan, $context->currentBuild->virtualFiles, RenameDiagnosticCollection::empty()),
                diagnostics: $diagnostics,
                touchedFiles: $touchedFiles,
                applied: false,
            );
        }

        $renameResult = $this->renamePlanApplier->apply($plan, $context->currentBuild);
        $this->mergeDiagnostics($diagnostics, $renameResult->diagnostics);

        if ($renameResult->diagnostics->hasErrors()) {
            return new RenameStepResult(
                context: $context,
                plan: $plan,
                renameResult: $renameResult,
                diagnostics: $diagnostics,
                touchedFiles: $touchedFiles,
                applied: false,
            );
        }

        return new RenameStepResult(
            context: $this->refreshContext($plan, $context),
            plan: $plan,
            renameResult: $renameResult,
            diagnostics: $diagnostics,
            touchedFiles: $touchedFiles,
            applied: 0 < count($plan->operations),
        );
    }

    /**
     * Refreshes the step context after one successful plan application.
     *
     * @param RenamePlan        $plan    the applied rename plan
     * @param RenameStepContext $context the previous step context
     */
    private function refreshContext(RenamePlan $plan, RenameStepContext $context): RenameStepContext
    {
        if (0 === count($plan->operations)) {
            return $context;
        }

        $overlay = $this->recordOverlayUpdate($context->overlay, $plan->request);

        if (null === $overlay) {
            $currentBuild = MemberDependencyGraphFactory::fromVirtualFiles($context->currentBuild->virtualFiles);

            return new RenameStepContext(
                baseBuild: $currentBuild,
                currentBuild: $currentBuild,
                overlay: MemberGraphBuildOverlay::empty(),
            );
        }

        return new RenameStepContext(
            baseBuild: $context->baseBuild,
            currentBuild: MemberGraphProjectedBuildFactory::fromBuild(
                build: $context->baseBuild,
                overlay: $overlay,
            ),
            overlay: $overlay,
        );
    }

    /**
     * Records one supported semantic identity update in the overlay.
     *
     * @param MemberGraphBuildOverlay $overlay the current overlay
     * @param RenameRequestInterface  $request the applied rename request
     */
    private function recordOverlayUpdate(
        MemberGraphBuildOverlay $overlay,
        RenameRequestInterface $request,
    ): ?MemberGraphBuildOverlay {
        if ($request instanceof ClassFqcnRenameRequest) {
            return $overlay->withOwnerUpdate($request->oldName(), $request->newName());
        }

        if ($request instanceof ClassRenameRequest) {
            return $overlay->withOwnerUpdate(
                owner: $request->className,
                newOwner: $this->namespacedName($request->className, $request->newClassName),
            );
        }

        if ($request instanceof MethodRenameRequest) {
            return $overlay->withMethodUpdate($request->className, $request->methodName, $request->newMethodName);
        }

        if ($request instanceof PropertyRenameRequest) {
            return $overlay->withPropertyUpdate($request->className, $request->propertyName, $request->newPropertyName);
        }

        if ($request instanceof ClassConstantRenameRequest) {
            return $overlay->withClassConstantUpdate($request->className, $request->constantName, $request->newConstantName);
        }

        if ($request instanceof EnumCaseRenameRequest) {
            return $overlay->withEnumCaseUpdate($request->enumName, $request->caseName, $request->newCaseName);
        }

        if ($request instanceof FunctionFqcnRenameRequest) {
            return $overlay->withFunctionUpdate($request->oldName(), $request->newName());
        }

        if ($request instanceof FunctionRenameRequest) {
            return $overlay->withFunctionUpdate(
                name: $request->functionName,
                newName: $this->namespacedName($request->functionName, $request->newFunctionName),
            );
        }

        if ($request instanceof ConstantFqcnRenameRequest) {
            return $overlay->withNamespaceConstantUpdate($request->oldName(), $request->newName());
        }

        if ($request instanceof ConstantRenameRequest) {
            return $overlay->withNamespaceConstantUpdate(
                name: $request->constantName,
                newName: $this->namespacedName($request->constantName, $request->newConstantName),
            );
        }

        if ($request instanceof ParameterRenameRequest) {
            return $overlay->withParameterUpdate(
                owner: $request->owner,
                functionLikeName: $request->functionLikeName,
                parameterName: $request->parameterName,
                newParameterName: $request->newParameterName,
                parameterIndex: $request->parameterIndex,
            );
        }

        return null;
    }

    /**
     * Replaces the short name part of one fully-qualified symbol name.
     *
     * @param string $currentName  the current fully-qualified symbol name
     * @param string $newShortName the replacement short name
     */
    private function namespacedName(string $currentName, string $newShortName): string
    {
        $parts = explode('\\', ltrim($currentName, '\\'));
        array_pop($parts);

        if ([] === $parts) {
            return $newShortName;
        }

        return implode('\\', $parts).'\\'.$newShortName;
    }

    /**
     * Collects the virtual files touched by one plan.
     *
     * @param RenamePlan $plan the rename plan to inspect
     */
    private function touchedFiles(RenamePlan $plan): VirtualPhpSourceFileCollection
    {
        $touchedFiles = new VirtualPhpSourceFileCollection();

        foreach ($plan->operations as $operation) {
            if ($touchedFiles->has($operation->file->virtualFilePath)) {
                continue;
            }

            $touchedFiles->add($operation->file);
        }

        return $touchedFiles;
    }

    /**
     * Adds diagnostics from one collection to another.
     *
     * @param RenameDiagnosticCollection $target the target diagnostics collection
     * @param RenameDiagnosticCollection $source the source diagnostics collection
     */
    private function mergeDiagnostics(RenameDiagnosticCollection $target, RenameDiagnosticCollection $source): void
    {
        foreach ($source as $diagnostic) {
            $target->add($diagnostic);
        }
    }
}
