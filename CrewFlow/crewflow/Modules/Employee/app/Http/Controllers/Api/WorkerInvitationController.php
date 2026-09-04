<?php

namespace Modules\Employee\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Authentication\Models\User;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Traits\ApiResponse;
use Modules\Employee\Mail\WorkerInvitationMail;
use Modules\Employee\Models\CompanyWorker;
use Modules\Employee\Models\Worker;

/**
 * The invite-by-email flow: an admin/dispatcher only ever types an
 * email address here — everything else (name, phone, password,
 * personal details) is filled in by the worker themselves when they
 * accept. This is deliberately the ONLY way a worker gets an account
 * from here on; the older "type everything yourself" flow
 * (CreateWorkerView on the frontend) still works but is no longer the
 * primary path.
 */
class WorkerInvitationController extends Controller
{
    use ApiResponse;

    /**
     * shifts.dispatch, not users.manage — both Company Admin and
     * Dispatcher can invite a worker, matching how both can already see
     * and manage shifts. Full profile/contract editing afterward still
     * requires users.manage (see WorkerController etc.).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'unique:users,email'],
        ]);

        $user = User::create([
            // Placeholder — the worker sets their real name when they
            // accept. Never shown/used for anything before that.
            'name' => explode('@', $data['email'])[0],
            'email' => $data['email'],
            'phone' => '',
            'password' => Hash::make(Str::random(40)), // unusable until they set their own
        ]);

        $worker = Worker::create(['user_id' => $user->id]);

        $token = Str::random(64);

        $companyWorker = CompanyWorker::create([
            'worker_id' => $worker->id,
            'status' => 'invited',
            'invitation_token' => $token,
            'invitation_expires_at' => now()->addDays(7),
        ]);

        $inviteUrl = rtrim(config('employee.worker_portal_url'), '/')
            .'?token='.$token
            .'&company='.tenant('company_code');

        Mail::to($user->email)->send(
            new WorkerInvitationMail($inviteUrl, tenant('name') ?? 'your company', tenant('company_code'))
        );

        return $this->success([
            'user_id' => $user->id,
            'company_worker_id' => $companyWorker->id,
        ], 'Invitation sent', 201);
    }

    /**
     * Public (no auth) — the worker doesn't have credentials yet. Lets a
     * future worker-portal page show "You've been invited to join
     * {company} as {email}" before asking them to accept.
     */
    public function show(string $token)
    {
        $companyWorker = CompanyWorker::with('worker.user')->where('invitation_token', $token)->first();

        if (! $companyWorker || $companyWorker->invitation_expires_at?->isPast()) {
            return $this->error('This invitation link is invalid or has expired.', 404);
        }

        return $this->success([
            'email' => $companyWorker->worker->user->email,
            'company_name' => tenant('name'),
            'company_code' => tenant('company_code'),
        ]);
    }

    /**
     * Public (no auth). The worker sets their real name/phone/password;
     * Worker and CompanyWorker both move from their initial state to
     * "pending" (an admin still needs to actually approve/contract them
     * — see the Employee module's README for the full status lifecycle).
     * Returns a fresh Sanctum token so they're immediately signed in.
     */
    public function accept(Request $request, string $token)
    {
        $companyWorker = CompanyWorker::with('worker.user')->where('invitation_token', $token)->first();

        if (! $companyWorker || $companyWorker->invitation_expires_at?->isPast()) {
            return $this->error('This invitation link is invalid or has expired.', 404);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $worker = $companyWorker->worker;
        $user = $worker->user;

        $user->update([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
        ]);

        $worker->update(['status' => 'pending']);

        $companyWorker->update([
            'status' => 'pending',
            'invitation_token' => null,
            'invitation_expires_at' => null,
            'joined_at' => now()->toDateString(),
        ]);

        $accessToken = $user->createToken('worker-token')->plainTextToken;

        return $this->success([
            'token' => $accessToken,
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
        ], 'Account set up');
    }
}
