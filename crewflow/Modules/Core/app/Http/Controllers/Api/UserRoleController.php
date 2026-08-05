<?php

namespace Modules\Core\Http\Controllers\Api;

use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Http\Resources\UserResource;
use Modules\Core\Models\User;
use Modules\Core\Traits\ApiResponse;

/**
 * Authorization for every action here is handled entirely at the route
 * level (permission:users.manage in routes/api.php).
 */
class UserRoleController extends Controller
{
    use ApiResponse;

    public function store(Request $request, User $user)
    {
        $data = $request->validate([
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $user->assignRole($data['role']);

        return $this->success(new UserResource($user->load('roles')), 'Role assigned');
    }

    /**
     * A company must always keep at least one Company Admin — refuses to
     * remove that role from the last remaining admin, the same safety
     * rule Organization applies to the last active branch.
     */
    public function destroy(User $user, string $role)
    {
        if ($role === 'Company Admin' && $user->hasRole('Company Admin')) {
            $adminCount = User::role('Company Admin')->count();

            if ($adminCount <= 1) {
                return $this->error('Cannot remove the last Company Admin.', 422);
            }
        }

        $user->removeRole($role);

        return $this->success(new UserResource($user->load('roles')), 'Role removed');
    }
}
