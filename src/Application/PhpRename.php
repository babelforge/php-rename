<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Application;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphFactory;
use PhpNoobs\PhpRename\Application\Contract\MethodRenamePlannerInterface;
use PhpNoobs\PhpRename\Application\Contract\PropertyRenamePlannerInterface;
use PhpNoobs\PhpRename\Application\Contract\RenamePlanApplierInterface;
use PhpNoobs\PhpRename\Domain\Rename\MethodRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\PropertyRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\RenamePlan;
use PhpNoobs\PhpRename\Domain\Rename\RenameResult;
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
     * @param MemberDependencyGraphBuild     $build                 the member graph build used by rename operations
     * @param MethodRenamePlannerInterface   $methodRenamePlanner   the method rename planner
     * @param PropertyRenamePlannerInterface $propertyRenamePlanner the property rename planner
     * @param RenamePlanApplierInterface     $renamePlanApplier     the rename plan applier
     */
    private function __construct(
        private MemberDependencyGraphBuild $build,
        private MethodRenamePlannerInterface $methodRenamePlanner,
        private PropertyRenamePlannerInterface $propertyRenamePlanner,
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
     * @param MemberDependencyGraphBuild          $build                 the member graph build
     * @param MethodRenamePlannerInterface|null   $methodRenamePlanner   the optional method rename planner override
     * @param RenamePlanApplierInterface|null     $renamePlanApplier     the optional rename plan applier override
     * @param PropertyRenamePlannerInterface|null $propertyRenamePlanner the optional property rename planner override
     */
    public static function fromBuild(
        MemberDependencyGraphBuild $build,
        ?MethodRenamePlannerInterface $methodRenamePlanner = null,
        ?RenamePlanApplierInterface $renamePlanApplier = null,
        ?PropertyRenamePlannerInterface $propertyRenamePlanner = null,
    ): self {
        return new self(
            build: $build,
            methodRenamePlanner: $methodRenamePlanner ?? new MemberGraphMethodRenamePlanner(),
            propertyRenamePlanner: $propertyRenamePlanner ?? new MemberGraphPropertyRenamePlanner(),
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
}
