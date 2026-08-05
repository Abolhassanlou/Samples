<?php

namespace Modules\Organization\Policies;

use Modules\Core\Models\User;
use Modules\Organization\Models\Branch;

class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // any authenticated tenant user can see the list of branches
    }

    public function view(User $user, Branch $branch): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('branches.manage');
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->can('branches.manage');
    }

    public function delete(User $user, Branch $branch): bool
    {
        return $user->can('branches.manage');
    }
}
