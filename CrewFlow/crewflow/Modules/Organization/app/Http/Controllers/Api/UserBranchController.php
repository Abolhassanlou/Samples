<?php

namespace Modules\Organization\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Authentication\Models\User;
use Modules\Core\Traits\ApiResponse;
use Modules\Organization\Models\Branch;

/**
 * Manages which branches a dispatcher is restricted to (see
 * BranchAccessService for the underlying rule: zero rows = unrestricted).
 *
 * Authorization is handled entirely at the route level — nested under the
 * same `permission:branches.manage` group as the rest of branch management
 * (see routes/api.php).
 */
class UserBranchController extends Controller
{
    use ApiResponse;

    public function index(User $user)
    {
        $branchIds = DB::table('user_branch')->where('user_id', $user->id)->pluck('branch_id');

        return $this->success([
            'user_id' => $user->id,
            'restricted' => $branchIds->isNotEmpty(),
            'branch_ids' => $branchIds,
        ]);
    }

    public function store(Request $request, User $user)
    {
        $data = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
        ]);

        DB::table('user_branch')->insertOrIgnore([
            'user_id' => $user->id,
            'branch_id' => $data['branch_id'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->success(null, 'Branch restriction added', 201);
    }

    public function destroy(User $user, Branch $branch)
    {
        DB::table('user_branch')->where('user_id', $user->id)->where('branch_id', $branch->id)->delete();

        return $this->success(null, 'Branch restriction removed');
    }
}
