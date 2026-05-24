<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Infrastructure\MemberGraph\Planner;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use BabelForge\MemberGraph\Application\Source\Node\MemberGraphSourceNodeLocator;
use BabelForge\MemberGraph\Application\Source\Node\VirtualPhpSourceFileNodeMatchRole;
use BabelForge\PhpRename\Application\Contract\NestedCallableLocalVariableRenamePlannerInterface;
use BabelForge\PhpRename\Application\Contract\NestedCallableRenamePlannerInterface;
use BabelForge\PhpRename\Domain\Rename\Conflict\RenameConflictPolicy;
use BabelForge\PhpRename\Domain\Rename\Diagnostic\RenameDiagnostic;
use BabelForge\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticCollection;
use BabelForge\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticSeverity;
use BabelForge\PhpRename\Domain\Rename\Operation\RenameOperation;
use BabelForge\PhpRename\Domain\Rename\Operation\RenameOperationCollection;
use BabelForge\PhpRename\Domain\Rename\Operation\RenameOperationRole;
use BabelForge\PhpRename\Domain\Rename\Plan\RenamePlan;
use BabelForge\PhpRename\Domain\Rename\Request\NestedCallableContainerKind;
use BabelForge\PhpRename\Domain\Rename\Request\NestedCallableKind;
use BabelForge\PhpRename\Domain\Rename\Request\NestedCallableLocalVariableRenameRequest;
use BabelForge\PhpRename\Domain\Rename\Request\NestedCallableRenameRequest;
use BabelForge\PhpRename\Domain\Rename\Symbol\RenameSymbolKind;
use PhpParser\Node;
use PhpParser\Node\ClosureUse;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;

/**
 * Plans nested callable renames from member-graph containers or virtual files.
 */
final readonly class MemberGraphNestedCallableRenamePlanner implements NestedCallableRenamePlannerInterface, NestedCallableLocalVariableRenamePlannerInterface
{
    /**
     * @var list<string>
     */
    private const array SUPERGLOBALS = [
        'GLOBALS',
        '_COOKIE',
        '_ENV',
        '_FILES',
        '_GET',
        '_POST',
        '_REQUEST',
        '_SERVER',
        '_SESSION',
    ];

    /**
     * Plans a nested callable parameter rename.
     *
     * @param NestedCallableRenameRequest $request the nested callable rename request
     * @param MemberDependencyGraphBuild  $build   the member graph build
     */
    public function plan(NestedCallableRenameRequest $request, MemberDependencyGraphBuild $build): RenamePlan
    {
        $operations = RenameOperationCollection::empty();
        $diagnostics = RenameDiagnosticCollection::empty();

        if ($request->oldName() === $request->newName()) {
            $diagnostics->add(new RenameDiagnostic(
                severity: RenameDiagnosticSeverity::WARNING,
                message: 'The requested nested callable parameter rename is a no-op.',
            ));

            return new RenamePlan($request, $operations, $diagnostics);
        }

        $container = $this->container($request, $build, $diagnostics);

        if (null === $container) {
            return new RenamePlan($request, $operations, $diagnostics);
        }

        $callable = $this->nestedCallable($container->node, $request);

        if (null === $callable) {
            $diagnostics->add(new RenameDiagnostic(
                severity: RenameDiagnosticSeverity::WARNING,
                message: sprintf('Nested %s at index %d was not found in the requested %s container.', $this->callableLabel($request), $request->callableIndex, strtolower($request->containerKind->name)),
            ));

            return new RenamePlan($request, $operations, $diagnostics);
        }

        $parameter = $this->parameter($callable, $request, $diagnostics);

        if (null === $parameter) {
            return new RenamePlan($request, $operations, $diagnostics);
        }

        $this->reportConflicts($diagnostics, $callable, $parameter, $request);

        $operations->add(new RenameOperation(
            symbolKind: RenameSymbolKind::PARAMETER,
            role: RenameOperationRole::DECLARATION,
            file: $container->file,
            node: $parameter,
            oldName: $request->oldName(),
            newName: $request->newName(),
        ));

        foreach ($this->localParameterUsages($callable, $request->oldName()) as $usage) {
            $operations->add(new RenameOperation(
                symbolKind: RenameSymbolKind::PARAMETER,
                role: RenameOperationRole::USAGE,
                file: $container->file,
                node: $usage,
                oldName: $request->oldName(),
                newName: $request->newName(),
            ));
        }

        return new RenamePlan($request, $operations, $diagnostics);
    }

    /**
     * Plans a nested callable local variable rename.
     *
     * @param NestedCallableLocalVariableRenameRequest $request the nested callable local variable rename request
     * @param MemberDependencyGraphBuild               $build   the member graph build
     */
    public function planLocalVariable(NestedCallableLocalVariableRenameRequest $request, MemberDependencyGraphBuild $build): RenamePlan
    {
        $operations = RenameOperationCollection::empty();
        $diagnostics = RenameDiagnosticCollection::empty();

        if ($request->oldName() === $request->newName()) {
            $diagnostics->add(new RenameDiagnostic(
                severity: RenameDiagnosticSeverity::WARNING,
                message: 'The requested nested callable local variable rename is a no-op.',
            ));

            return new RenamePlan($request, $operations, $diagnostics);
        }

        if ($this->isSuperglobalName($request->oldName()) || $this->isSuperglobalName($request->newName())) {
            $diagnostics->add(new RenameDiagnostic(
                severity: RenameDiagnosticSeverity::ERROR,
                message: 'Nested callable local variable rename cannot target PHP superglobals.',
            ));

            return new RenamePlan($request, $operations, $diagnostics);
        }

        $container = $this->container($request, $build, $diagnostics);

        if (null === $container) {
            return new RenamePlan($request, $operations, $diagnostics);
        }

        $callable = $this->nestedCallable($container->node, $request);

        if (null === $callable) {
            $diagnostics->add(new RenameDiagnostic(
                severity: RenameDiagnosticSeverity::WARNING,
                message: sprintf('Nested %s at index %d was not found in the requested %s container.', $this->callableLabel($request), $request->callableIndex, strtolower($request->containerKind->name)),
            ));

            return new RenamePlan($request, $operations, $diagnostics);
        }

        $usages = $this->localVariableUsages($callable, $request->oldName());

        if ([] === $usages) {
            $diagnostics->add(new RenameDiagnostic(
                severity: RenameDiagnosticSeverity::WARNING,
                message: 'Nested callable local variable was not found for the requested rename.',
            ));

            return new RenamePlan($request, $operations, $diagnostics);
        }

        $this->reportLocalVariableConflicts($diagnostics, $callable, $request);

        foreach ($usages as $usage) {
            $operations->add(new RenameOperation(
                symbolKind: RenameSymbolKind::LOCAL_VARIABLE,
                role: RenameOperationRole::USAGE,
                file: $container->file,
                node: $usage,
                oldName: $request->oldName(),
                newName: $request->newName(),
            ));
        }

        return new RenamePlan($request, $operations, $diagnostics);
    }

    /**
     * Resolves the container node and virtual file.
     *
     * @param NestedCallableRenameRequest|NestedCallableLocalVariableRenameRequest $request     the nested callable request
     * @param MemberDependencyGraphBuild                                           $build       the member graph build
     * @param RenameDiagnosticCollection                                           $diagnostics the diagnostics to append to
     */
    private function container(
        NestedCallableRenameRequest|NestedCallableLocalVariableRenameRequest $request,
        MemberDependencyGraphBuild $build,
        RenameDiagnosticCollection $diagnostics,
    ): ?NestedCallableContainer {
        if (NestedCallableContainerKind::FILE === $request->containerKind) {
            return $this->fileContainer($request, $build, $diagnostics);
        }

        $matches = NestedCallableContainerKind::METHOD === $request->containerKind
            ? MemberGraphSourceNodeLocator::fromBuild($build)->method((string) $request->className, (string) $request->methodName)
            : MemberGraphSourceNodeLocator::fromBuild($build)->function((string) $request->functionName);

        foreach ($matches as $match) {
            if (VirtualPhpSourceFileNodeMatchRole::MEMBER_DECLARATION !== $match->role) {
                continue;
            }

            if (NestedCallableContainerKind::METHOD === $request->containerKind && !$match->node instanceof ClassMethod) {
                continue;
            }

            if (NestedCallableContainerKind::FUNCTION === $request->containerKind && !$match->node instanceof Function_) {
                continue;
            }

            return new NestedCallableContainer($match->virtualFile, $match->node);
        }

        $diagnostics->add(new RenameDiagnostic(
            severity: RenameDiagnosticSeverity::WARNING,
            message: sprintf('No %s container was found for the requested nested callable parameter rename.', strtolower($request->containerKind->name)),
        ));

        return null;
    }

    /**
     * Resolves a file container from the build virtual files.
     *
     * @param NestedCallableRenameRequest|NestedCallableLocalVariableRenameRequest $request     the nested callable request
     * @param MemberDependencyGraphBuild                                           $build       the member graph build
     * @param RenameDiagnosticCollection                                           $diagnostics the diagnostics to append to
     */
    private function fileContainer(
        NestedCallableRenameRequest|NestedCallableLocalVariableRenameRequest $request,
        MemberDependencyGraphBuild $build,
        RenameDiagnosticCollection $diagnostics,
    ): ?NestedCallableContainer {
        $filePath = (string) $request->filePath;
        $realPath = realpath($filePath);
        $realFilePath = false === $realPath ? $filePath : $realPath;

        foreach ($build->virtualFiles as $virtualFile) {
            if ($virtualFile->fullFilePath !== $realFilePath && $virtualFile->virtualFilePath !== $filePath) {
                continue;
            }

            return new NestedCallableContainer($virtualFile, array_values($virtualFile->nodes));
        }

        $diagnostics->add(new RenameDiagnostic(
            severity: RenameDiagnosticSeverity::WARNING,
            message: sprintf('File container "%s" was not found for the requested nested callable parameter rename.', $filePath),
        ));

        return null;
    }

    /**
     * Finds a nested callable by deterministic DFS index.
     *
     * @param Node|list<Node>                                                      $container the container node or nodes
     * @param NestedCallableRenameRequest|NestedCallableLocalVariableRenameRequest $request   the nested callable request
     */
    private function nestedCallable(Node|array $container, NestedCallableRenameRequest|NestedCallableLocalVariableRenameRequest $request): Closure|ArrowFunction|null
    {
        $matches = $this->collectNestedCallables($container, $request->callableKind);

        return $matches[$request->callableIndex] ?? null;
    }

    /**
     * Collects nested callables in DFS order.
     *
     * @param Node|array<array-key, mixed> $nodeOrNodes the node or node list to inspect
     * @param NestedCallableKind           $kind        the callable kind to collect
     *
     * @return list<Closure|ArrowFunction>
     */
    private function collectNestedCallables(Node|array $nodeOrNodes, NestedCallableKind $kind): array
    {
        $nodes = $nodeOrNodes instanceof Node ? [$nodeOrNodes] : $nodeOrNodes;
        $matches = [];

        foreach ($nodes as $node) {
            if (!$node instanceof Node) {
                continue;
            }

            if (NestedCallableKind::CLOSURE === $kind && $node instanceof Closure) {
                $matches[] = $node;
            }

            if (NestedCallableKind::ARROW_FUNCTION === $kind && $node instanceof ArrowFunction) {
                $matches[] = $node;
            }

            foreach (get_object_vars($node) as $subNode) {
                if ($subNode instanceof Node || is_array($subNode)) {
                    array_push($matches, ...$this->collectNestedCallables($subNode, $kind));
                }
            }
        }

        return $matches;
    }

    /**
     * Resolves the targeted parameter declaration.
     *
     * @param Closure|ArrowFunction       $callable    the nested callable node
     * @param NestedCallableRenameRequest $request     the nested callable request
     * @param RenameDiagnosticCollection  $diagnostics the diagnostics to append to
     */
    private function parameter(
        Closure|ArrowFunction $callable,
        NestedCallableRenameRequest $request,
        RenameDiagnosticCollection $diagnostics,
    ): ?Param {
        foreach ($callable->getParams() as $index => $parameter) {
            if (null !== $request->parameterIndex && $index !== $request->parameterIndex) {
                continue;
            }

            if ($this->parameterName($parameter) !== $request->oldName()) {
                continue;
            }

            return $parameter;
        }

        $diagnostics->add(new RenameDiagnostic(
            severity: RenameDiagnosticSeverity::WARNING,
            message: 'Nested callable parameter was not found for the requested rename.',
        ));

        return null;
    }

    /**
     * Reports scoped nested callable parameter conflicts.
     *
     * @param RenameDiagnosticCollection  $diagnostics the diagnostics to append to
     * @param Closure|ArrowFunction       $callable    the nested callable node
     * @param Param                       $target      the targeted parameter
     * @param NestedCallableRenameRequest $request     the nested callable request
     */
    private function reportConflicts(
        RenameDiagnosticCollection $diagnostics,
        Closure|ArrowFunction $callable,
        Param $target,
        NestedCallableRenameRequest $request,
    ): void {
        foreach ($callable->getParams() as $parameter) {
            if ($parameter === $target) {
                continue;
            }

            if ($this->parameterName($parameter) === $request->newName()) {
                $this->addConflict($diagnostics, $request, 'Nested callable parameter rename conflicts with another parameter in the same callable.');

                return;
            }
        }

        foreach ($this->directLocalVariables($callable) as $variable) {
            if ($variable->name === $request->newName()) {
                $this->addConflict($diagnostics, $request, 'Nested callable parameter rename conflicts with a local variable in the same callable.');

                return;
            }
        }
    }

    /**
     * Reports scoped nested callable local variable conflicts.
     *
     * @param RenameDiagnosticCollection               $diagnostics the diagnostics to append to
     * @param Closure|ArrowFunction                    $callable    the nested callable node
     * @param NestedCallableLocalVariableRenameRequest $request     the nested callable local variable request
     */
    private function reportLocalVariableConflicts(
        RenameDiagnosticCollection $diagnostics,
        Closure|ArrowFunction $callable,
        NestedCallableLocalVariableRenameRequest $request,
    ): void {
        if ($this->hasParameterNamed($callable, $request->newName())) {
            $this->addLocalVariableConflict($diagnostics, $request, 'Nested callable local variable rename conflicts with a parameter in the same callable.');

            return;
        }

        foreach ($this->directLocalVariables($callable) as $variable) {
            if ($variable->name === $request->newName()) {
                $this->addLocalVariableConflict($diagnostics, $request, 'Nested callable local variable rename conflicts with another local variable in the same callable.');

                return;
            }
        }
    }

    /**
     * Adds a conflict diagnostic according to the request policy.
     *
     * @param RenameDiagnosticCollection  $diagnostics the diagnostics to append to
     * @param NestedCallableRenameRequest $request     the nested callable request
     * @param string                      $message     the diagnostic message
     */
    private function addConflict(
        RenameDiagnosticCollection $diagnostics,
        NestedCallableRenameRequest $request,
        string $message,
    ): void {
        $diagnostics->add(new RenameDiagnostic(
            severity: RenameConflictPolicy::FAIL === $request->conflictPolicy
                ? RenameDiagnosticSeverity::ERROR
                : RenameDiagnosticSeverity::WARNING,
            message: $message,
        ));
    }

    /**
     * Adds a local variable conflict diagnostic according to the request policy.
     *
     * @param RenameDiagnosticCollection               $diagnostics the diagnostics to append to
     * @param NestedCallableLocalVariableRenameRequest $request     the nested callable local variable request
     * @param string                                   $message     the diagnostic message
     */
    private function addLocalVariableConflict(
        RenameDiagnosticCollection $diagnostics,
        NestedCallableLocalVariableRenameRequest $request,
        string $message,
    ): void {
        $diagnostics->add(new RenameDiagnostic(
            severity: RenameConflictPolicy::FAIL === $request->conflictPolicy
                ? RenameDiagnosticSeverity::ERROR
                : RenameDiagnosticSeverity::WARNING,
            message: $message,
        ));
    }

    /**
     * Collects direct local parameter usages in the selected callable scope.
     *
     * @param Closure|ArrowFunction $callable      the selected callable
     * @param string                $parameterName the parameter name without "$"
     *
     * @return list<Variable|ClosureUse>
     */
    private function localParameterUsages(Closure|ArrowFunction $callable, string $parameterName): array
    {
        $root = $callable instanceof Closure ? $callable->stmts : $callable->expr;

        return $this->collectParameterUsages($root, $parameterName);
    }

    /**
     * Collects local variable usages in the selected callable scope.
     *
     * @param Closure|ArrowFunction $callable     the selected callable
     * @param string                $variableName the variable name without "$"
     *
     * @return list<Variable|ClosureUse>
     */
    private function localVariableUsages(Closure|ArrowFunction $callable, string $variableName): array
    {
        $root = $callable instanceof Closure ? $callable->stmts : $callable->expr;

        return $this->collectParameterUsages($root, $variableName);
    }

    /**
     * Collects direct local variables in the selected callable scope.
     *
     * @param Closure|ArrowFunction $callable the selected callable
     *
     * @return list<Variable>
     */
    private function directLocalVariables(Closure|ArrowFunction $callable): array
    {
        $root = $callable instanceof Closure ? $callable->stmts : $callable->expr;

        return $this->collectVariables($root, null);
    }

    /**
     * Collects parameter usages while following nested callable captures conservatively.
     *
     * @param Node|array<array-key, mixed> $nodeOrNodes   the node or node list to inspect
     * @param string                       $parameterName the parameter name without "$"
     *
     * @return list<Variable|ClosureUse>
     */
    private function collectParameterUsages(Node|array $nodeOrNodes, string $parameterName): array
    {
        $nodes = $nodeOrNodes instanceof Node ? [$nodeOrNodes] : $nodeOrNodes;
        $usages = [];

        foreach ($nodes as $node) {
            if (!$node instanceof Node) {
                continue;
            }

            if ($node instanceof Closure) {
                array_push($usages, ...$this->nestedClosureParameterUsages($node, $parameterName));

                continue;
            }

            if ($node instanceof ArrowFunction) {
                array_push($usages, ...$this->nestedArrowFunctionParameterUsages($node, $parameterName));

                continue;
            }

            if ($node instanceof Variable && is_string($node->name) && $node->name === $parameterName) {
                $usages[] = $node;
            }

            foreach (get_object_vars($node) as $subNode) {
                if ($subNode instanceof Node || is_array($subNode)) {
                    array_push($usages, ...$this->collectParameterUsages($subNode, $parameterName));
                }
            }
        }

        return $usages;
    }

    /**
     * Collects captured parameter usages in one nested closure.
     *
     * @param Closure $closure       the nested closure node
     * @param string  $parameterName the parameter name without "$"
     *
     * @return list<Variable|ClosureUse>
     */
    private function nestedClosureParameterUsages(Closure $closure, string $parameterName): array
    {
        if ($this->hasParameterNamed($closure, $parameterName)) {
            return [];
        }

        $usages = [];

        foreach ($closure->uses as $use) {
            if (is_string($use->var->name) && $use->var->name === $parameterName) {
                $usages[] = $use;
            }
        }

        if ([] === $usages) {
            return [];
        }

        array_push($usages, ...$this->collectParameterUsages($closure->stmts, $parameterName));

        return $usages;
    }

    /**
     * Collects implicitly captured parameter usages in one nested arrow function.
     *
     * @param ArrowFunction $arrowFunction the nested arrow-function node
     * @param string        $parameterName the parameter name without "$"
     *
     * @return list<Variable|ClosureUse>
     */
    private function nestedArrowFunctionParameterUsages(ArrowFunction $arrowFunction, string $parameterName): array
    {
        if ($this->hasParameterNamed($arrowFunction, $parameterName)) {
            return [];
        }

        return $this->collectParameterUsages($arrowFunction->expr, $parameterName);
    }

    /**
     * Indicates whether one function-like node declares a parameter with the requested name.
     *
     * @param Closure|ArrowFunction $callable      the callable node to inspect
     * @param string                $parameterName the parameter name without "$"
     */
    private function hasParameterNamed(Closure|ArrowFunction $callable, string $parameterName): bool
    {
        foreach ($callable->getParams() as $parameter) {
            if ($this->parameterName($parameter) === $parameterName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Indicates whether one variable name is a PHP superglobal.
     *
     * @param string $name the variable name without "$"
     */
    private function isSuperglobalName(string $name): bool
    {
        return in_array($name, self::SUPERGLOBALS, true);
    }

    /**
     * Collects variable nodes while skipping nested callable scopes.
     *
     * @param Node|array<array-key, mixed> $nodeOrNodes the node or node list to inspect
     * @param string|null                  $name        the optional variable name filter
     *
     * @return list<Variable>
     */
    private function collectVariables(Node|array $nodeOrNodes, ?string $name): array
    {
        $nodes = $nodeOrNodes instanceof Node ? [$nodeOrNodes] : $nodeOrNodes;
        $variables = [];

        foreach ($nodes as $node) {
            if (!$node instanceof Node) {
                continue;
            }

            if ($node instanceof Closure || $node instanceof ArrowFunction) {
                continue;
            }

            if ($node instanceof Variable && is_string($node->name) && (null === $name || $node->name === $name)) {
                $variables[] = $node;
            }

            foreach (get_object_vars($node) as $subNode) {
                if ($subNode instanceof Node || is_array($subNode)) {
                    array_push($variables, ...$this->collectVariables($subNode, $name));
                }
            }
        }

        return $variables;
    }

    /**
     * Returns the parameter name without "$".
     *
     * @param Param $parameter the parameter declaration
     */
    private function parameterName(Param $parameter): ?string
    {
        if (!$parameter->var instanceof Variable || !is_string($parameter->var->name)) {
            return null;
        }

        return $parameter->var->name;
    }

    /**
     * Returns a lowercase callable label for diagnostics.
     *
     * @param NestedCallableRenameRequest|NestedCallableLocalVariableRenameRequest $request the nested callable request
     */
    private function callableLabel(NestedCallableRenameRequest|NestedCallableLocalVariableRenameRequest $request): string
    {
        return NestedCallableKind::CLOSURE === $request->callableKind ? 'closure' : 'arrow function';
    }
}
