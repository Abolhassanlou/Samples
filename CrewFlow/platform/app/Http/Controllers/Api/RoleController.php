<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Fully dynamic, same philosophy as crewflow's Authorization module —
 * `Super Admin` / `Support Agent` are just a starting seed
 * (see PlatformDatabaseSeeder), not hardcoded.
 */
class RoleController extends Controller
{
    use ApiResponse;

    public function index()
    {
        return $this->success(Role::with('permissions')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'api']);

        if (! empty($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return $this->success($role->load('permissions'), 'Role created', 201);
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255', 'unique:roles,name,'.$role->id],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        if (isset($data['name'])) {
            $role->update(['name' => $data['name']]);
        }

        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return $this->success($role->fresh('permissions'), 'Role updated');
    }

    public function destroy(Role $role)
    {
        $role->delete();

        return $this->success(null, 'Role deleted');
    }

    public function permissions()
    {
        return $this->success(Permission::all(['id', 'name']));
    }
}
