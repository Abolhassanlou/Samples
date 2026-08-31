<?php

namespace Modules\Employee\Http\Controllers\Api;

use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Traits\ApiResponse;
use Modules\Employee\Http\Resources\WorkerDirectoryResource;
use Modules\Employee\Models\WorkerProfile;

/**
 * A dispatcher-facing search/filter directory — deliberately distinct
 * from Authentication's Users listing (an access-control concern gated
 * by users.manage, usually admin-only). This is about finding the right
 * worker to staff a shift: by qualification, home branch, and
 * availability at a specific day/time. Gated by shifts.dispatch, so
 * both Company Admin and Dispatcher can use it — see routes/api.php.
 */
class WorkerDirectoryController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = WorkerProfile::query()
            ->with(['user', 'homeBranch', 'qualifications.qualification', 'availability']);

        if ($request->filled('branch_id')) {
            $query->where('home_branch_id', $request->integer('branch_id'));
        }

        if ($request->filled('qualification_id')) {
            $query->whereHas('qualifications', function ($q) use ($request) {
                $q->where('qualification_id', $request->integer('qualification_id'));
            });
        }

        // Both day_of_week (0=Sunday..6=Saturday) and time (HH:MM) must be
        // given together — a worker matches if they have an availability
        // slot on that day spanning that time.
        if ($request->filled('day_of_week') && $request->filled('time')) {
            $query->whereHas('availability', function ($q) use ($request) {
                $q->where('day_of_week', $request->integer('day_of_week'))
                    ->where('start_time', '<=', $request->string('time'))
                    ->where('end_time', '>=', $request->string('time'));
            });
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('personnel_number', 'like', "%{$search}%");
            });
        }

        return $this->success(WorkerDirectoryResource::collection($query->get()));
    }
}
