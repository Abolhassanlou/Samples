<?php

namespace Modules\Setting\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Modules\Core\Traits\ApiResponse;
use Modules\Setting\Http\Requests\CompanySettingsRequest;
use Modules\Setting\Http\Resources\CompanySettingsResource;
use Modules\Setting\Models\CompanySettings;

/**
 * Singleton resource — there is exactly one settings row per tenant
 * database, so no {id} in the URL. Viewing is open to any authenticated
 * company user (e.g. a worker's app may need shift_visibility_mode to
 * decide how to render the shift list); editing requires settings.manage.
 */
class CompanySettingsController extends Controller
{
    use ApiResponse;

    public function show()
    {
        return $this->success(new CompanySettingsResource(CompanySettings::current()));
    }

    public function update(CompanySettingsRequest $request)
    {
        $settings = CompanySettings::current();
        $settings->update($request->validated());

        return $this->success(new CompanySettingsResource($settings), 'Settings updated');
    }
}
