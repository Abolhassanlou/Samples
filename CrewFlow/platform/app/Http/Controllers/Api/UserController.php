<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlatformUser;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $users = PlatformUser::with('roles')->get()->map(fn (PlatformUser $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'roles' => $u->getRoleNames(),
            'created_at' => $u->created_at,
        ]);

        return $this->success($users);
    }

    public function assignRole(Request $request, PlatformUser $user)
    {
        $data = $request->validate([
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $user->assignRole($data['role']);

        return $this->success(['roles' => $user->getRoleNames()], 'Role assigned');
    }

    public function removeRole(Request $request, PlatformUser $user, string $role)
    {
        if ($role === 'Super Admin' && $user->hasRole('Super Admin')) {
            $superAdminCount = PlatformUser::role('Super Admin')->count();

            if ($superAdminCount <= 1) {
                return $this->error('Cannot remove the last Super Admin.', 422);
            }
        }

        $user->removeRole($role);

        return $this->success(['roles' => $user->getRoleNames()], 'Role removed');
    }
}
