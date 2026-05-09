<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Application\Contract;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\PhpRename\Domain\Rename\Plan\RenamePlan;
use PhpNoobs\PhpRename\Domain\Rename\Request\PropertyRenameRequest;

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
