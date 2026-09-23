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
     *
     * Before the normal "email must be unique" check, this looks for the
     * specific case of a worker who LEFT (CompanyWorker.status is
     * `inactive` or `blocked`) coming back under the same email — rather
     * than a flat validation error, it returns a distinct 409 with
     * `errors.reactivatable: true` so the frontend can offer "Reactivate
     * them instead?" (see reactivate() below) rather than a dead end. An
     * email belonging to a currently active/pending/invited worker, or
     * to a non-worker account (e.g. another admin), still gets the plain
     * "already taken" validation error — there's nothing to reactivate.
     */
    public function store(Request $request)
    {
        $email = $request->input('email');

        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            $existingWorker = Worker::where('user_id', $existingUser->id)->first();
            $existingCompanyWorker = $existingWorker
                ? CompanyWorker::where('worker_id', $existingWorker->id)->first()
                : null;

            if ($existingCompanyWorker && in_array($existingCompanyWorker->status, ['inactive', 'blocked'])) {
                return $this->error(
                    "A worker with this email already exists but is currently {$existingCompanyWorker->status}. Reactivate them instead of creating a new invitation?",
                    409,
                    ['reactivatable' => true, 'user_id' => $existingUser->id, 'current_status' => $existingCompanyWorker->status]
                );
            }
        }

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

        $companyWorker = CompanyWorker::create([
            'worker_id' => $worker->id,
            'status' => 'invited',
        ]);

        $this->sendInvitation($companyWorker, $user);

        return $this->success([
            'user_id' => $user->id,
            'company_worker_id' => $companyWorker->id,
        ], 'Invitation sent', 201);
    }

    /**
     * The other side of the 409 in store() above — an admin/dispatcher
     * confirms they actually want to bring this specific worker back,
     * rather than store() silently reactivating on a bare retry (that
     * would let a plain "invite" accidentally resurrect someone a
     * different admin deliberately deactivated). Everything about the
     * worker — Worker, all their documents, their full contract history
     * — is untouched; only CompanyWorker's status/invitation fields
     * reset, exactly like a fresh invite. Worker.status itself isn't
     * touched here either — accept() (unchanged) sets it back to
     * `pending` once they actually complete the new invitation, same as
     * any other accept.
     */
    public function reactivate(Request $request, User $user)
    {
        $worker = Worker::where('user_id', $user->id)->first();
        abort_unless($worker, 404);

        $companyWorker = CompanyWorker::where('worker_id', $worker->id)->first();
        abort_unless($companyWorker, 404);
        abort_unless(in_array($companyWorker->status, ['inactive', 'blocked']), 422, 'This worker is not in a reactivatable state.');

        $companyWorker->update(['status' => 'invited']);

        $this->sendInvitation($companyWorker, $user);

        return $this->success([
            'user_id' => $user->id,
            'company_worker_id' => $companyWorker->id,
        ], 'Reactivation invitation sent');
    }

    /**
     * Shared by store() (a brand new invite) and reactivate() (a
     * returning worker) — generates a fresh token, sets its expiry, and
     * sends the same email either way.
     */
    private function sendInvitation(CompanyWorker $companyWorker, User $user): void
    {
        $token = Str::random(64);

        $companyWorker->update([
            'invitation_token' => $token,
            'invitation_expires_at' => now()->addDays(7),
        ]);

        $inviteUrl = rtrim(config('employee.worker_portal_url'), '/')
            .'?token='.$token
            .'&company='.tenant('company_code');

        Mail::to($user->email)->send(
            new WorkerInvitationMail($inviteUrl, tenant('name') ?? 'your company', tenant('company_code'))
        );
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
     * Public (no auth). Deliberately asks for only a password now —
     * name/phone (and everything else about the worker) is filled in
     * later from their own Profile once they're actually in the app,
     * rather than duplicating that entry here too. `name` keeps its
     * email-prefix placeholder (set in store() above) until the worker
     * fills in Personal details, which updates it for real (see
     * PersonalDetailsForm.vue in worker-portal) — `phone` stays empty
     * the same way. Worker and CompanyWorker both move from their
     * initial state to "pending" (an admin still needs to actually
     * approve/contract them — see the Employee module's README for the
     * full status lifecycle). Returns a fresh Sanctum token so they're
     * immediately signed in.
     */
    public function accept(Request $request, string $token)
    {
        $companyWorker = CompanyWorker::with('worker.user')->where('invitation_token', $token)->first();

        if (! $companyWorker || $companyWorker->invitation_expires_at?->isPast()) {
            return $this->error('This invitation link is invalid or has expired.', 404);
        }

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $worker = $companyWorker->worker;
        $user = $worker->user;

        $user->update(['password' => Hash::make($data['password'])]);

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
