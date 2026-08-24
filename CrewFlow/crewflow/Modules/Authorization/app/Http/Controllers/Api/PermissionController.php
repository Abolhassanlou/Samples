<?php

namespace Modules\Authorization\Http\Controllers\Api;

use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Traits\ApiResponse;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    use ApiResponse;

    public function index()
    {
        return $this->success(Permission::all(['id', 'name']));
    }
}
