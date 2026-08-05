<?php

namespace Modules\Organization\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Modules\Core\Traits\ApiResponse;
use Modules\Organization\Http\Requests\BranchRequest;
use Modules\Organization\Http\Resources\BranchResource;
use Modules\Organization\Models\Branch;

class BranchController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $this->authorize('viewAny', Branch::class);

        return $this->success(BranchResource::collection(Branch::orderByDesc('is_main')->get()));
    }

    public function store(BranchRequest $request)
    {
        $this->authorize('create', Branch::class);

        $branch = Branch::create($request->validated());

        if ($branch->is_main) {
            $this->makeOnlyMain($branch);
        }

        return $this->success(new BranchResource($branch), 'Branch created', 201);
    }

    public function update(BranchRequest $request, Branch $branch)
    {
        $this->authorize('update', $branch);

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
        $this->authorize('delete', $branch);

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
