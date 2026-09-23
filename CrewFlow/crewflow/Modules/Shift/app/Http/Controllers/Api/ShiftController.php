<?php

namespace Modules\Shift\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Traits\ApiResponse;
use Modules\Shift\Http\Requests\ShiftRequest;
use Modules\Shift\Http\Resources\ShiftResource;
use Modules\Shift\Models\Shift;
use Modules\Shift\Services\ShiftVisibility;

/**
 * Authorization for every mutating action here is handled entirely at
 * the route level (permission:shifts.create in routes/api.php).
 *
 * Viewing: a Dispatcher/Admin (shifts.dispatch) always sees every shift,
 * unfiltered — they need full visibility to manage. A plain Worker only
 * sees shifts they have both ACCESS to (their own branch, or an Event
 * they've been explicitly activated for) AND every required qualification
 * for (see ShiftVisibility for the full rule). Hidden, not just disabled.
 */
class ShiftController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Shift::with([
            'positions.role',
            'positions.requiredQualifications.qualification',
            'confirmedAssignments.worker',
        ])->orderByDesc('starts_at');

        if (! $request->user()->can('shifts.dispatch')) {
            $query = ShiftVisibility::scopeFor($query, $request->user());
            // A worker browsing for open work shouldn't see cancelled,
            // in-progress, or completed shifts — but "partially_filled"
            // (some spots taken, more still open) must stay visible
            // alongside "open", or a shift vanishes from the browsable
            // list the moment even one worker is confirmed, even with
            // quantity_needed > 1 still leaving room. A dispatcher/admin
            // still sees every status (they need to manage shifts
            // regardless of where they're at).
            $query->whereIn('status', ['open', 'partially_filled']);
        }

        return $this->success(ShiftResource::collection($query->get()));
    }

    public function store(ShiftRequest $request)
    {
        $shift = Shift::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
            'status' => 'open',
            'location_type' => $request->validated('location_type', 'on_site'),
            'quantity_needed' => $request->validated('quantity_needed', 1),
            'rate_type' => $request->validated('rate_type', 'hourly'),
            'qualification_policy' => $request->validated('qualification_policy', 'strict'),
        ]);

        return $this->success(new ShiftResource($shift), 'Shift created', 201);
    }

    /**
     * A worker hitting this directly for a shift outside their visibility
     * gets a 404, not a 403 — consistent with "hidden", not "disabled".
     */
    public function show(Request $request, Shift $shift)
    {
        if (! $request->user()->can('shifts.dispatch')) {
            $visible = ShiftVisibility::scopeFor(Shift::where('id', $shift->id), $request->user())->exists();
            abort_unless($visible, 404);
        }

        return $this->success(new ShiftResource($shift->load(['positions.role', 'positions.requiredQualifications.qualification', 'confirmedAssignments.worker'])));
    }

    public function update(ShiftRequest $request, Shift $shift)
    {
        $data = $request->validated();

        // Captured BEFORE $shift->update() overwrites them — needed to
        // build a human-readable diff afterward.
        $oldStartsAt = $shift->starts_at;
        $oldEndsAt = $shift->ends_at;
        $oldLocation = $shift->location_address;

        // Date fields need real datetime comparison, not string
        // comparison — the incoming value ("2026-09-15T20:00" from an
        // HTML datetime-local input, say) and the stored Carbon value's
        // string form ("2026-09-15 20:00:00") never match byte-for-byte
        // even when they're the exact same moment, which would falsely
        // trigger a reconfirmation reset on every edit, not just ones
        // that actually change the time.
        $timeChanged = false;
        foreach (['starts_at', 'ends_at'] as $field) {
            if (array_key_exists($field, $data) && ! Carbon::parse($data[$field])->equalTo($shift->{$field})) {
                $timeChanged = true;
                break;
            }
        }
        $locationChanged = array_key_exists('location_address', $data) && $data['location_address'] !== $oldLocation;

        $shift->update($data);

        // If the timing or location actually changed, any worker who
        // had already confirmed no longer has a guarantee the shift
        // still fits their schedule — reset their confirmation back to
        // pending_worker_confirmation so they have to look at the new
        // details and confirm again, rather than silently staying
        // "confirmed" for a shift that's moved. Only timing/location
        // trigger this — an edited description or contact name doesn't.
        // change_note is set to a plain-language summary of exactly
        // what changed, so the worker isn't left guessing why they're
        // suddenly being asked to confirm again — see
        // AssignmentResource and the worker-portal Jobs tab, which
        // surfaces this prominently.
        if ($timeChanged || $locationChanged) {
            $changeNote = $this->buildChangeNote($timeChanged, $locationChanged, $oldStartsAt, $oldEndsAt, $shift->starts_at, $shift->ends_at, $oldLocation, $shift->location_address);

            $shift->assignments()
                ->where('status', 'confirmed')
                ->update(['status' => 'pending_worker_confirmation', 'confirmed_at' => null, 'change_note' => $changeNote]);

            if ($shift->status === 'filled') {
                $shift->update(['status' => 'partially_filled']);
            }
        }

        return $this->success(new ShiftResource($shift), 'Shift updated');
    }

    private function buildChangeNote(bool $timeChanged, bool $locationChanged, $oldStart, $oldEnd, $newStart, $newEnd, ?string $oldLocation, ?string $newLocation): string
    {
        $parts = [];

        if ($timeChanged) {
            $fmt = fn ($d) => $d->format('D, M j, g:i A');
            $parts[] = "Time changed from {$fmt($oldStart)}–{$fmt($oldEnd)} to {$fmt($newStart)}–{$fmt($newEnd)}.";
        }

        if ($locationChanged) {
            $from = $oldLocation ?: 'not set';
            $to = $newLocation ?: 'not set';
            $parts[] = "Location changed from \"{$from}\" to \"{$to}\".";
        }

        return implode(' ', $parts);
    }

    /**
     * A real, permanent delete — distinct from setting status to
     * "cancelled" (via update()), which keeps the shift and its full
     * interest/assignment history intact, just marked as not happening.
     * This actually removes the row, and — since positions,
     * qualifications, interests, and assignments all cascadeOnDelete()
     * off shift_id — everything tied to it too. There is no undo.
     * Prefer cancelling unless this was a genuine mistake with no
     * history worth keeping.
     */
    public function destroy(Shift $shift)
    {
        $shift->delete();

        return $this->success(null, 'Shift deleted');
    }
}
