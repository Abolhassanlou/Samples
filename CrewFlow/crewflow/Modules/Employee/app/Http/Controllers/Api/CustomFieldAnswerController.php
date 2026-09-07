<?php

namespace Modules\Employee\Http\Controllers\Api;

use Illuminate\Http\Request;
use Modules\Authentication\Models\User;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Traits\ApiResponse;
use Modules\Employee\Http\Resources\CustomFieldAnswerResource;
use Modules\Employee\Models\CustomFieldAnswer;

/**
 * A worker's own answers to the company's custom questions. A worker
 * can view/set their own; users.manage can view/set anyone's — same
 * self-or-admin pattern as WorkerAvailabilityController.
 */
class CustomFieldAnswerController extends Controller
{
    use ApiResponse;

    public function index(Request $request, User $user)
    {
        abort_unless($request->user()->id === $user->id || $request->user()->can('users.manage'), 403);

        $answers = CustomFieldAnswer::where('worker_id', $user->id)->get();

        return $this->success(CustomFieldAnswerResource::collection($answers));
    }

    /**
     * Full replace, same as availability sync — the frontend always
     * sends every question's current answer together (empty/unanswered
     * ones simply aren't included).
     */
    public function sync(Request $request, User $user)
    {
        abort_unless($request->user()->id === $user->id || $request->user()->can('users.manage'), 403);

        $data = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*.custom_field_definition_id' => ['required', 'integer', 'exists:custom_field_definitions,id'],
            'answers.*.value' => ['nullable', 'string'],
        ]);

        foreach ($data['answers'] as $answer) {
            CustomFieldAnswer::updateOrCreate(
                ['custom_field_definition_id' => $answer['custom_field_definition_id'], 'worker_id' => $user->id],
                ['value' => $answer['value'] ?? null]
            );
        }

        $fresh = CustomFieldAnswer::where('worker_id', $user->id)->get();

        return $this->success(CustomFieldAnswerResource::collection($fresh), 'Answers saved');
    }
}
