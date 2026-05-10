<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Application;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphFactory;
use PhpNoobs\PhpRename\Application\Contract\ClassConstantRenamePlannerInterface;
use PhpNoobs\PhpRename\Application\Contract\ClassFqcnRenamePlannerInterface;
use PhpNoobs\PhpRename\Application\Contract\ClassRenamePlannerInterface;
use PhpNoobs\PhpRename\Application\Contract\FunctionFqcnRenamePlannerInterface;
use PhpNoobs\PhpRename\Application\Contract\FunctionRenamePlannerInterface;
use PhpNoobs\PhpRename\Application\Contract\MethodRenamePlannerInterface;
use PhpNoobs\PhpRename\Application\Contract\PropertyRenamePlannerInterface;
use PhpNoobs\PhpRename\Application\Contract\RenamePlanApplierInterface;
use PhpNoobs\PhpRename\Domain\Rename\Plan\RenamePlan;
use PhpNoobs\PhpRename\Domain\Rename\Plan\RenameResult;
use PhpNoobs\PhpRename\Domain\Rename\Request\ClassConstantRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\Request\ClassFqcnRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\Request\ClassRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\Request\FunctionFqcnRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\Request\FunctionRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\Request\MethodRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\Request\PropertyRenameRequest;
use PhpNoobs\PhpRename\Infrastructure\MemberGraph\MemberGraphClassConstantRenamePlanner;
use PhpNoobs\PhpRename\Infrastructure\MemberGraph\MemberGraphClassFqcnRenamePlanner;
use PhpNoobs\PhpRename\Infrastructure\MemberGraph\MemberGraphClassRenamePlanner;
use PhpNoobs\PhpRename\Infrastructure\MemberGraph\MemberGraphFunctionFqcnRenamePlanner;
use PhpNoobs\PhpRename\Infrastructure\MemberGraph\MemberGraphFunctionRenamePlanner;
use PhpNoobs\PhpRename\Infrastructure\MemberGraph\MemberGraphMethodRenamePlanner;
use PhpNoobs\PhpRename\Infrastructure\MemberGraph\MemberGraphPropertyRenamePlanner;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\AstRenamePlanApplier;

/**
 * Public facade for planning and applying PHP symbol renames.
 */
final readonly class PhpRename
{
    /**
     * Constructor.
     *
     * @param MemberDependencyGraphBuild          $build                      the member graph build used by rename operations
     * @param MethodRenamePlannerInterface        $methodRenamePlanner        the method rename planner
     * @param PropertyRenamePlannerInterface      $propertyRenamePlanner      the property rename planner
     * @param ClassConstantRenamePlannerInterface $classConstantRenamePlanner the class-constant rename planner
     * @param ClassRenamePlannerInterface         $classRenamePlanner         the class rename planner
     * @param ClassFqcnRenamePlannerInterface     $classFqcnRenamePlanner     the class FQCN rename planner
     * @param FunctionRenamePlannerInterface      $functionRenamePlanner      the function rename planner
     * @param FunctionFqcnRenamePlannerInterface  $functionFqcnRenamePlanner  the function FQCN rename planner
     * @param RenamePlanApplierInterface          $renamePlanApplier          the rename plan applier
     */
    private function __construct(
        private MemberDependencyGraphBuild $build,
        private MethodRenamePlannerInterface $methodRenamePlanner,
        private PropertyRenamePlannerInterface $propertyRenamePlanner,
        private ClassConstantRenamePlannerInterface $classConstantRenamePlanner,
        private ClassRenamePlannerInterface $classRenamePlanner,
        private ClassFqcnRenamePlannerInterface $classFqcnRenamePlanner,
        private FunctionRenamePlannerInterface $functionRenamePlanner,
        private FunctionFqcnRenamePlannerInterface $functionFqcnRenamePlanner,
        private RenamePlanApplierInterface $renamePlanApplier,
    ) {
    }

    /**
     * Creates a renamer from project directories.
     *
     * @param list<string> $directories         the directories to scan
     * @param string       $cacheFilePath       the member graph cache file path
     * @param list<string> $excludedDirectories the directories to exclude from scanning
     * @param bool         $clearCache          whether the member graph cache must be cleared first
     */
    public static function fromDirectory(
        array $directories,
        string $cacheFilePath,
        array $excludedDirectories = [],
        bool $clearCache = false,
    ): self {
        return self::fromBuild(MemberDependencyGraphFactory::fromDirectory(
            directories: $directories,
            cacheFilePath: $cacheFilePath,
            excludedDirectories: $excludedDirectories,
            clearCache: $clearCache,
        ));
    }

    /**
     * Creates a renamer from an existing member graph build.
     *
     * @param MemberDependencyGraphBuild               $build                      the member graph build
     * @param MethodRenamePlannerInterface|null        $methodRenamePlanner        the optional method rename planner override
     * @param RenamePlanApplierInterface|null          $renamePlanApplier          the optional rename plan applier override
     * @param PropertyRenamePlannerInterface|null      $propertyRenamePlanner      the optional property rename planner override
     * @param ClassConstantRenamePlannerInterface|null $classConstantRenamePlanner the optional class-constant rename planner override
     * @param ClassRenamePlannerInterface|null         $classRenamePlanner         the optional class rename planner override
     * @param ClassFqcnRenamePlannerInterface|null     $classFqcnRenamePlanner     the optional class FQCN rename planner override
     * @param FunctionRenamePlannerInterface|null      $functionRenamePlanner      the optional function rename planner override
     * @param FunctionFqcnRenamePlannerInterface|null  $functionFqcnRenamePlanner  the optional function FQCN rename planner override
     */
    public static function fromBuild(
        MemberDependencyGraphBuild $build,
        ?MethodRenamePlannerInterface $methodRenamePlanner = null,
        ?RenamePlanApplierInterface $renamePlanApplier = null,
        ?PropertyRenamePlannerInterface $propertyRenamePlanner = null,
        ?ClassConstantRenamePlannerInterface $classConstantRenamePlanner = null,
        ?ClassRenamePlannerInterface $classRenamePlanner = null,
        ?ClassFqcnRenamePlannerInterface $classFqcnRenamePlanner = null,
        ?FunctionRenamePlannerInterface $functionRenamePlanner = null,
        ?FunctionFqcnRenamePlannerInterface $functionFqcnRenamePlanner = null,
    ): self {
        return new self(
            build: $build,
            methodRenamePlanner: $methodRenamePlanner ?? new MemberGraphMethodRenamePlanner(),
            propertyRenamePlanner: $propertyRenamePlanner ?? new MemberGraphPropertyRenamePlanner(),
            classConstantRenamePlanner: $classConstantRenamePlanner ?? new MemberGraphClassConstantRenamePlanner(),
            classRenamePlanner: $classRenamePlanner ?? new MemberGraphClassRenamePlanner(),
            classFqcnRenamePlanner: $classFqcnRenamePlanner ?? new MemberGraphClassFqcnRenamePlanner(),
            functionRenamePlanner: $functionRenamePlanner ?? new MemberGraphFunctionRenamePlanner(),
            functionFqcnRenamePlanner: $functionFqcnRenamePlanner ?? new MemberGraphFunctionFqcnRenamePlanner(),
            renamePlanApplier: $renamePlanApplier ?? new AstRenamePlanApplier(),
        );
    }

    /**
     * Plans a semantic method rename.
     *
     * @param string $className     the class name that anchors the method rename
     * @param string $methodName    the current method name
     * @param string $newMethodName the replacement method name
     *
     * @throws \InvalidArgumentException when one rename input is empty
     */
    public function planMethodRename(string $className, string $methodName, string $newMethodName): RenamePlan
    {
        return $this->methodRenamePlanner->plan(
            request: new MethodRenameRequest($className, $methodName, $newMethodName),
            build: $this->build,
        );
    }

    /**
     * Plans and applies a semantic method rename to virtual file AST nodes.
     *
     * @param string $className     the class name that anchors the method rename
     * @param string $methodName    the current method name
     * @param string $newMethodName the replacement method name
     *
     * @throws \InvalidArgumentException when one rename input is empty
     */
    public function renameMethod(string $className, string $methodName, string $newMethodName): RenameResult
    {
        return $this->renamePlanApplier->apply(
            plan: $this->planMethodRename($className, $methodName, $newMethodName),
            build: $this->build,
        );
    }

    /**
     * Plans a semantic property rename.
     *
     * @param string $className       the class name that anchors the property rename
     * @param string $propertyName    the current property name
     * @param string $newPropertyName the replacement property name
     *
     * @throws \InvalidArgumentException when one rename input is empty
     */
    public function planPropertyRename(string $className, string $propertyName, string $newPropertyName): RenamePlan
    {
        return $this->propertyRenamePlanner->plan(
            request: new PropertyRenameRequest($className, $propertyName, $newPropertyName),
            build: $this->build,
        );
    }

    /**
     * Plans and applies a semantic property rename to virtual file AST nodes.
     *
     * @param string $className       the class name that anchors the property rename
     * @param string $propertyName    the current property name
     * @param string $newPropertyName the replacement property name
     *
     * @throws \InvalidArgumentException when one rename input is empty
     */
    public function renameProperty(string $className, string $propertyName, string $newPropertyName): RenameResult
    {
        return $this->renamePlanApplier->apply(
            plan: $this->planPropertyRename($className, $propertyName, $newPropertyName),
            build: $this->build,
        );
    }

    /**
     * Plans a semantic class-constant rename.
     *
     * @param string $className       the class name that anchors the class-constant rename
     * @param string $constantName    the current class-constant name
     * @param string $newConstantName the replacement class-constant name
     *
     * @throws \InvalidArgumentException when one rename input is empty
     */
    public function planClassConstantRename(string $className, string $constantName, string $newConstantName): RenamePlan
    {
        return $this->classConstantRenamePlanner->plan(
            request: new ClassConstantRenameRequest($className, $constantName, $newConstantName),
            build: $this->build,
        );
    }

    /**
     * Plans and applies a semantic class-constant rename to virtual file AST nodes.
     *
     * @param string $className       the class name that anchors the class-constant rename
     * @param string $constantName    the current class-constant name
     * @param string $newConstantName the replacement class-constant name
     *
     * @throws \InvalidArgumentException when one rename input is empty
     */
    public function renameClassConstant(string $className, string $constantName, string $newConstantName): RenameResult
    {
        return $this->renamePlanApplier->apply(
            plan: $this->planClassConstantRename($className, $constantName, $newConstantName),
            build: $this->build,
        );
    }

    /**
     * Plans a semantic class-like owner rename.
     *
     * @param string $className    the current fully-qualified class-like owner name
     * @param string $newClassName the replacement short class-like owner name
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function planClassRename(string $className, string $newClassName): RenamePlan
    {
        return $this->classRenamePlanner->plan(
            request: new ClassRenameRequest($className, $newClassName),
            build: $this->build,
        );
    }

    /**
     * Plans and applies a semantic class-like owner rename to virtual file AST nodes.
     *
     * @param string $className    the current fully-qualified class-like owner name
     * @param string $newClassName the replacement short class-like owner name
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function renameClass(string $className, string $newClassName): RenameResult
    {
        return $this->renamePlanApplier->apply(
            plan: $this->planClassRename($className, $newClassName),
            build: $this->build,
        );
    }

    /**
     * Plans a semantic class-like owner rename to another fully-qualified name.
     *
     * @param string $className    the current fully-qualified class-like owner name
     * @param string $newClassName the replacement fully-qualified class-like owner name
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function planClassFqcnRename(string $className, string $newClassName): RenamePlan
    {
        return $this->classFqcnRenamePlanner->plan(
            request: new ClassFqcnRenameRequest($className, $newClassName),
            build: $this->build,
        );
    }

    /**
     * Plans and applies a semantic class-like owner FQCN rename to virtual file AST nodes.
     *
     * @param string $className    the current fully-qualified class-like owner name
     * @param string $newClassName the replacement fully-qualified class-like owner name
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function renameClassFqcn(string $className, string $newClassName): RenameResult
    {
        return $this->renamePlanApplier->apply(
            plan: $this->planClassFqcnRename($className, $newClassName),
            build: $this->build,
        );
    }

    /**
     * Plans a semantic function rename.
     *
     * @param string $functionName    the current fully-qualified function name
     * @param string $newFunctionName the replacement short function name
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function planFunctionRename(string $functionName, string $newFunctionName): RenamePlan
    {
        return $this->functionRenamePlanner->plan(
            request: new FunctionRenameRequest($functionName, $newFunctionName),
            build: $this->build,
        );
    }

    /**
     * Plans and applies a semantic function rename to virtual file AST nodes.
     *
     * @param string $functionName    the current fully-qualified function name
     * @param string $newFunctionName the replacement short function name
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function renameFunction(string $functionName, string $newFunctionName): RenameResult
    {
        return $this->renamePlanApplier->apply(
            plan: $this->planFunctionRename($functionName, $newFunctionName),
            build: $this->build,
        );
    }

    /**
     * Plans a semantic function rename to another fully-qualified name.
     *
     * @param string $functionName    the current fully-qualified function name
     * @param string $newFunctionName the replacement fully-qualified function name
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function planFunctionFqcnRename(string $functionName, string $newFunctionName): RenamePlan
    {
        return $this->functionFqcnRenamePlanner->plan(
            request: new FunctionFqcnRenameRequest($functionName, $newFunctionName),
            build: $this->build,
        );
    }

    /**
     * Plans and applies a semantic function FQCN rename to virtual file AST nodes.
     *
     * @param string $functionName    the current fully-qualified function name
     * @param string $newFunctionName the replacement fully-qualified function name
     *
     * @throws \InvalidArgumentException when one rename input is invalid
     */
    public function renameFunctionFqcn(string $functionName, string $newFunctionName): RenameResult
    {
        return $this->renamePlanApplier->apply(
            plan: $this->planFunctionFqcnRename($functionName, $newFunctionName),
            build: $this->build,
        );
    }
}
