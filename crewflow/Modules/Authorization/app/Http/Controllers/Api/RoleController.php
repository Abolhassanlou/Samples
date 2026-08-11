<?php

namespace Modules\Authorization\Http\Controllers\Api;

use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\Controller;
use Modules\Authorization\Http\Resources\RoleResource;
use Modules\Authorization\Models\Role;
use Modules\Core\Traits\ApiResponse;

/**
 * Authorization for every action here is handled entirely at the route
 * level (permission:roles.manage in routes/api.php).
 *
 * Roles are fully dynamic: a Company Admin can create as many custom
 * roles as they want, with any combination of permissions. The three
 * seeded roles (Company Admin, Dispatcher, Worker) are just a starting
 * point — `is_system` only protects their NAME (so code that references
 * them by name, e.g. the default role assigned at worker registration,
 * never breaks), not their permission set. A Company Admin can freely
 * change what permissions Dispatcher or Worker have.
 */
class RoleController extends Controller
{
    use ApiResponse;

    public function index()
    {
        return $this->success(RoleResource::collection(Role::with('permissions')->get()));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => 'api',
            'is_system' => false,
        ]);

        if (! empty($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return $this->success(new RoleResource($role->load('permissions')), 'Role created', 201);
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255', 'unique:roles,name,'.$role->id],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        if ($role->is_system && isset($data['name']) && $data['name'] !== $role->name) {
            return $this->error('System roles cannot be renamed (their permissions can still be changed).', 422);
        }

        if (isset($data['name'])) {
            $role->update(['name' => $data['name']]);
        }

        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return $this->success(new RoleResource($role->fresh('permissions')), 'Role updated');
    }

    public function destroy(Role $role)
    {
        if ($role->is_system) {
            return $this->error('System roles cannot be deleted.', 422);
        }

        $role->delete();

        return $this->success(null, 'Role deleted');
    }
}
