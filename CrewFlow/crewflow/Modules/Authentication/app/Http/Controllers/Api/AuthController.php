<?php

namespace Modules\Authentication\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\Core\Http\Controllers\Controller;
use Modules\Authentication\Http\Requests\LoginRequest;
use Modules\Authentication\Http\Requests\RegisterRequest;
use Modules\Authentication\Http\Resources\UserResource;
use Modules\Authentication\Models\User;
use Modules\Core\Traits\ApiResponse;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Register a new user.
     *
     * Important: tenant resolution (based on the company_code the user
     * entered) happens in Tenancy module middleware, before the request
     * reaches this controller. This means that by the time this method
     * runs, the database connection is already switched to that specific
     * company's (tenant's) database, and this User is created there.
     */
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'password' => Hash::make($request->validated('password')),
        ]);

        // Default role for any registration made via a company_code: Worker
        $user->assignRole('Worker');

        $token = $user->createToken('api-token')->plainTextToken;

        return $this->success([
            'user' => new UserResource($user),
            'token' => $token,
        ], 'Registration successful', 201);
    }

    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->validated('email'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            return $this->error('Invalid email or password', 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return $this->success([
            'user' => new UserResource($user),
            'token' => $token,
        ], 'Login successful');
    }

    public function logout()
    {
        auth()->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logged out successfully');
    }

    public function me()
    {
        return $this->success(new UserResource(auth()->user()));
    }

    /**
     * Self-service update of a user's own name/phone — deliberately
     * separate from Authentication's users.manage-gated endpoints (there
     * is no such endpoint for editing someone ELSE's name/phone; a
     * worker only ever updates their own). Nothing here touches roles,
     * permissions, email, or password.
     */
    public function updateMe(Request $request)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20'],
        ]);

        $user = auth()->user();
        $user->update($data);

        return $this->success(new UserResource($user), 'Profile updated');
    }
}
