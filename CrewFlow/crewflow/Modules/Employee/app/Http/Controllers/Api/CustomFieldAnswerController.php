<?php

namespace Modules\Employee\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Authentication\Models\User;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Traits\ApiResponse;
use Modules\Employee\Http\Resources\CustomFieldAnswerResource;
use Modules\Employee\Models\CustomFieldAnswer;
use Modules\Employee\Models\CustomFieldDefinition;

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
     *
     * A question marked `is_required` (and still active) can't be saved
     * blank: each such answer in the request that has no real value is
     * rejected with "<label> is required." against its own
     * `answers.N.value`, so whichever client sends it gets a message it
     * can show. Checked per submitted answer, deliberately NOT across
     * every required question the company has — Personal details and
     * Skills each submit only their own category's questions through this
     * same endpoint, and a blank required skill must not stop someone
     * saving their personal details. (A client that simply leaves a
     * required question out of the request isn't caught here; the
     * worker-portal form always sends every field in its category.)
     */
    public function sync(Request $request, User $user)
    {
        abort_unless($request->user()->id === $user->id || $request->user()->can('users.manage'), 403);

        $validator = Validator::make($request->all(), [
            'answers' => ['required', 'array'],
            'answers.*.custom_field_definition_id' => ['required', 'integer', 'exists:custom_field_definitions,id'],
            'answers.*.value' => ['nullable', 'string'],
        ]);

        $validator->after(function ($validator) use ($request) {
            // Only once the shape itself is valid — a malformed id or
            // value would otherwise be dereferenced below.
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $answers = $request->input('answers');
            $definitions = CustomFieldDefinition::whereIn('id', collect($answers)->pluck('custom_field_definition_id'))
                ->get()
                ->keyBy('id');

            foreach ($answers as $index => $answer) {
                $definition = $definitions->get($answer['custom_field_definition_id']);

                if ($definition && $definition->is_required && $definition->is_active
                    && $this->isBlank($definition->field_type, $answer['value'] ?? null)) {
                    $validator->errors()->add("answers.{$index}.value", "{$definition->label} is required.");
                }
            }
        });

        $data = $validator->validate();

        foreach ($data['answers'] as $answer) {
            CustomFieldAnswer::updateOrCreate(
                ['custom_field_definition_id' => $answer['custom_field_definition_id'], 'worker_id' => $user->id],
                ['value' => $answer['value'] ?? null]
            );
        }

        $fresh = CustomFieldAnswer::where('worker_id', $user->id)->get();

        return $this->success(CustomFieldAnswerResource::collection($fresh), 'Answers saved');
    }

    /**
     * "No answer" for a required question. Whitespace-only text is blank,
     * and a multi_select with nothing ticked is blank (it's stored as the
     * JSON array "[]", which is a non-empty string). `0` is a real answer
     * — PHP's empty('0') is true, which is why this isn't empty(). A
     * boolean is never blank: an unticked box is still an answer, and the
     * form can't submit one without a value anyway.
     */
    private function isBlank(string $fieldType, ?string $value): bool
    {
        if ($fieldType === 'boolean') {
            return false;
        }

        if ($value === null || trim($value) === '') {
            return true;
        }

        if ($fieldType === 'multi_select') {
            $decoded = json_decode($value, true);

            return ! is_array($decoded) || count($decoded) === 0;
        }

        return false;
    }
}
