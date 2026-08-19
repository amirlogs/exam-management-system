<?php

namespace App\Http\Controllers;

use App\Http\Filters\RequestFilters;
use App\Http\Resources\PermissionResource;
use App\Models\Permission;
use App\Validation\GetRequestsValidator;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        $query = Permission::query();
        RequestFilters::apply($query, $request, ['name' , 'category']);
        
        // return $query->paginate($per_page);
        $permissions = $query->get();
        
        return $this->success(PermissionResource::collection($permissions) , "Permissions Retrieved Successfully");
    }

    
}
