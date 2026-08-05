<?php

namespace Modules\Organization\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Models\User;

/**
 * Encapsulates the "is this dispatcher restricted to specific branches"
 * rule. Deliberately implemented here (Organization), not as a relation on
 * Core's User model, since Core must not depend on Organization (Branch).
 *
 * Rule: a user with zero rows in `user_branch` has unrestricted access to
 * every branch. A user with one or more rows is restricted to exactly
 * those branches.
 */
class BranchAccessService
{
    public function isRestricted(User $user): bool
    {
        return DB::table('user_branch')->where('user_id', $user->id)->exists();
    }

    /**
     * @return array<int>|null Null means unrestricted (every branch allowed).
     */
    public function allowedBranchIds(User $user): ?array
    {
        $ids = DB::table('user_branch')
            ->where('user_id', $user->id)
            ->pluck('branch_id')
            ->all();

        return $ids === [] ? null : $ids;
    }
}
