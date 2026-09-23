<?php

namespace Modules\Authentication\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Core\Http\Controllers\Controller;
use Modules\Authentication\Http\Requests\LoginRequest;
use Modules\Authentication\Http\Requests\RegisterRequest;
use Modules\Authentication\Http\Resources\UserResource;
use Modules\Authentication\Mail\PasswordResetMail;
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

    /**
     * Self-service, unauthenticated. No restriction based on Worker/
     * CompanyWorker status — Authentication doesn't know about those
     * (Employee depends on Authentication, not the other way around),
     * so even a worker whose CompanyWorker is inactive/blocked can reset
     * their own password here; that only gets them back into a session,
     * not back onto shifts — WorkerEligibility and everything else
     * downstream still gates on their actual status regardless. An
     * admin/dispatcher who wants to fully restore a departed worker's
     * standing still uses the Employee module's reactivate() flow.
     *
     * Deliberately returns the same success message whether or not the
     * email actually exists — this endpoint can never be used to check
     * which emails are registered. `redirect_url` is the frontend's own
     * reset-password page base (admin panel and worker portal each pass
     * their own) — this module has no fixed config for it since it
     * doesn't know in advance which of the two apps is calling.
     */
    public function forgotPassword(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'redirect_url' => ['required', 'url'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if ($user) {
            $token = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                ['token' => Hash::make($token), 'created_at' => now()]
            );

            // company= alongside token=/email=, same reasoning as the
            // Employee module's invite links — the reset-password page
            // gets opened fresh, with no session yet to read the tenant
            // subdomain from otherwise.
            $resetUrl = rtrim($data['redirect_url'], '/')
                .'?token='.$token
                .'&email='.urlencode($user->email)
                .'&company='.tenant('company_code');

            Mail::to($user->email)->send(new PasswordResetMail($resetUrl));
        }

        return $this->success(null, 'If an account with that email exists, a reset link has been sent.');
    }

    /**
     * Public (no auth — the whole point is the user is locked out).
     * The token itself is stored hashed (Hash::check() below), same
     * treatment as a real password, so a leaked database dump can't be
     * used to forge reset links. Expires after 1 hour, consumed
     * (deleted) on first successful use either way.
     */
    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $data['email'])->first();

        if (! $record || ! Hash::check($data['token'], $record->token) || now()->diffInMinutes($record->created_at) > 60) {
            return $this->error('This reset link is invalid or has expired. Request a new one.', 400);
        }

        $user = User::where('email', $data['email'])->first();
        abort_unless($user, 404);

        $user->update(['password' => Hash::make($data['password'])]);

        DB::table('password_reset_tokens')->where('email', $data['email'])->delete();

        return $this->success(null, 'Password reset — you can sign in with your new password now.');
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
