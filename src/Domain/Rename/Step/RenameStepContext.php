<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Domain\Rename\Step;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use BabelForge\MemberGraph\Application\Build\Projection\MemberGraphBuildOverlay;

/**
 * Carries the semantic state needed to execute one orchestrable rename step.
 */
final readonly class RenameStepContext
{
    /**
     * Constructor.
     *
     * @param MemberDependencyGraphBuild $baseBuild    the base build used by overlay projection
     * @param MemberDependencyGraphBuild $currentBuild the current build used to plan and apply the next step
     * @param MemberGraphBuildOverlay    $overlay      the cumulative rename overlay
     */
    public function __construct(
        public MemberDependencyGraphBuild $baseBuild,
        public MemberDependencyGraphBuild $currentBuild,
        public MemberGraphBuildOverlay $overlay,
    ) {
    }

    /**
     * Creates an initial step context from one build.
     *
     * @param MemberDependencyGraphBuild $build the initial member graph build
     */
    public static function fromBuild(MemberDependencyGraphBuild $build): self
    {
        return new self(
            baseBuild: $build,
            currentBuild: $build,
            overlay: MemberGraphBuildOverlay::empty(),
        );
    }
}
