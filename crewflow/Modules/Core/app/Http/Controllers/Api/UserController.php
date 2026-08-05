<?php

namespace Modules\Core\Http\Controllers\Api;

use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Http\Resources\UserResource;
use Modules\Core\Models\User;
use Modules\Core\Traits\ApiResponse;

/**
 * Authorization for every action here is handled entirely at the route
 * level (permission:users.manage in routes/api.php), not inside these
 * methods.
 */
class UserController extends Controller
{
    use ApiResponse;

    public function index()
    {
        return $this->success(UserResource::collection(User::with('roles')->get()));
    }

    public function show(User $user)
    {
        return $this->success(new UserResource($user->load('roles')));
    }
}
