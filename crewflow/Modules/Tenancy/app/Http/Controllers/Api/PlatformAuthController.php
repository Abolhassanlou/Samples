<?php

namespace Modules\Tenancy\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Modules\Tenancy\Http\Requests\PlatformLoginRequest;
use Modules\Tenancy\Models\PlatformUser;

/**
 * Deliberately has NO register endpoint. Platform admin accounts are
 * created only via the `platform:make-admin` artisan command (by the
 * first super admin, or during initial deployment) — self-registration
 * for platform-level access would be a serious security hole.
 */
class PlatformAuthController extends Controller
{
    public function login(PlatformLoginRequest $request)
    {
        $user = PlatformUser::where('email', $request->validated('email'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password',
            ], 401);
        }

        $token = $user->createToken('platform-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames(),
                ],
                'token' => $token,
            ],
        ]);
    }

    public function logout()
    {
        auth()->user()?->currentAccessToken()->delete();

        return response()->json(['success' => true, 'message' => 'Logged out']);
    }

    public function me()
    {
        /** @var PlatformUser $user */
        $user = auth()->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ],
        ]);
    }
}
