<?php

namespace Modules\Organization\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Modules\Core\Traits\ApiResponse;
use Modules\Organization\Http\Requests\BranchRequest;
use Modules\Organization\Http\Resources\BranchResource;
use Modules\Organization\Models\Branch;

/**
 * Authorization for every mutating action here is handled entirely at
 * the route level (permission:branches.manage in routes/api.php).
 */
class BranchController extends Controller
{
    use ApiResponse;

    public function index()
    {
        return $this->success(BranchResource::collection(Branch::orderByDesc('is_main')->get()));
    }

    public function store(BranchRequest $request)
    {
        $branch = Branch::create($request->validated());

        if ($branch->is_main) {
            $this->makeOnlyMain($branch);
        }

        return $this->success(new BranchResource($branch), 'Branch created', 201);
    }

    public function update(BranchRequest $request, Branch $branch)
    {
        $branch->update($request->validated());

        if ($branch->is_main) {
            $this->makeOnlyMain($branch);
        }

        return $this->success(new BranchResource($branch), 'Branch updated');
    }

    /**
     * "Delete" here means deactivate, never a hard delete — historical
     * shifts/workers may still reference this branch. A company must
     * always keep at least one active branch (this is what lets a
     * single-branch company stay simple: there is never a state with
     * zero branches to fall back to).
     */
    public function destroy(Branch $branch)
    {
        $activeCount = Branch::where('is_active', true)->count();

        if ($activeCount <= 1) {
            return $this->error('Cannot deactivate the only active branch. Create another branch first.', 422);
        }

        $branch->update(['is_active' => false, 'is_main' => false]);

        if ($branch->wasChanged('is_main')) {
            $replacement = Branch::where('is_active', true)->where('id', '!=', $branch->id)->first();
            $replacement?->update(['is_main' => true]);
        }

        return $this->success(null, 'Branch deactivated');
    }

    private function makeOnlyMain(Branch $branch): void
    {
        Branch::where('id', '!=', $branch->id)->update(['is_main' => false]);
    }
}
