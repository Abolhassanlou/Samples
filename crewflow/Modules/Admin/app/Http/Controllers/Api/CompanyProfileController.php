<?php

namespace Modules\Admin\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\Admin\Http\Requests\CompanyProfileRequest;
use Modules\Admin\Http\Resources\CompanyProfileResource;
use Modules\Admin\Models\CompanyProfile;
use Modules\Core\Traits\ApiResponse;

class CompanyProfileController extends Controller
{
    use ApiResponse;

    public function show()
    {
        return $this->success(new CompanyProfileResource(CompanyProfile::current()));
    }

    public function update(CompanyProfileRequest $request)
    {
        $profile = CompanyProfile::current();

        $data = $request->safe()->except('logo');

        if ($request->hasFile('logo')) {
            if ($profile->logo_path) {
                Storage::disk('local')->delete($profile->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('company-logo', 'local');
        }

        $profile->update($data);

        return $this->success(new CompanyProfileResource($profile), 'Company profile updated');
    }

    public function logo()
    {
        $profile = CompanyProfile::current();

        abort_unless($profile->logo_path && Storage::disk('local')->exists($profile->logo_path), 404);

        return Storage::disk('local')->response($profile->logo_path);
    }
}
