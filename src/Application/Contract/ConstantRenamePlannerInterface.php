<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Application\Contract;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use BabelForge\PhpRename\Domain\Rename\Plan\RenamePlan;
use BabelForge\PhpRename\Domain\Rename\Request\ConstantRenameRequest;

/**
 * Plans namespace-level constant renames.
 */
interface ConstantRenamePlannerInterface
{
    /**
     * Plans one namespace-level constant rename.
     *
     * @param ConstantRenameRequest      $request the constant rename request
     * @param MemberDependencyGraphBuild $build   the member graph build
     */
    public function plan(ConstantRenameRequest $request, MemberDependencyGraphBuild $build): RenamePlan;
}
