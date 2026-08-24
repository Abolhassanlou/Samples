<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\PlatformUser;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Deliberately has NO register endpoint — platform admin accounts are
 * created only via `php artisan platform:make-admin` (interactive,
 * console-only). Self-registration for platform-level access would be a
 * serious security hole.
 */
class AuthController extends Controller
{
    use ApiResponse;

    public function login(LoginRequest $request)
    {
        $user = PlatformUser::where('email', $request->validated('email'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            return $this->error('Invalid email or password', 401);
        }

        $token = $user->createToken('platform-token')->plainTextToken;

        return $this->success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ],
            'token' => $token,
        ], 'Login successful');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logged out');
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return $this->success([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }
}
