<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Application\Contract;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use BabelForge\PhpRename\Domain\Rename\Plan\RenamePlan;
use BabelForge\PhpRename\Domain\Rename\Request\PropertyRenameRequest;

/**
 * Plans property rename operations from a semantic member graph build.
 */
interface PropertyRenamePlannerInterface
{
    /**
     * Plans a property rename.
     *
     * @param PropertyRenameRequest      $request the property rename request
     * @param MemberDependencyGraphBuild $build   the member graph build used to resolve declarations and usages
     */
    public function plan(PropertyRenameRequest $request, MemberDependencyGraphBuild $build): RenamePlan;
}
