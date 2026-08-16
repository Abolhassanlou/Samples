<?php

namespace Modules\Payment\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Traits\ApiResponse;
use Modules\Payment\Models\WorkerRating;
use Modules\Shift\Models\Assignment;

class WorkerRatingController extends Controller
{
    use ApiResponse;

    /**
     * Bidirectional, per the business-model doc: someone with
     * shifts.dispatch can rate on behalf of the company ("internal") or
     * on behalf of the client ("client", since the client itself has no
     * login — the dispatcher relays their feedback).
     */
    public function store(Request $request, Assignment $assignment)
    {
        $data = $request->validate([
            'rated_by_type' => ['required', 'in:client,internal'],
            'score' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        $rating = WorkerRating::create([
            'assignment_id' => $assignment->id,
            'worker_id' => $assignment->worker_id,
            'rated_by_type' => $data['rated_by_type'],
            'rated_by' => $request->user()->id,
            'score' => $data['score'],
            'comment' => $data['comment'] ?? null,
        ]);

        return $this->success($rating, 'Rating recorded', 201);
    }
}
