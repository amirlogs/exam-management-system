<?php

namespace App\Http\Controllers;

use App\Http\Filters\RequestFilters;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Validation\GetRequestsValidator;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function store(StoreRoleRequest $request)
    {
        $validated = $request->validated();
        $role = Role::create($validated);

        return $this->success(new RoleResource($role), 'Role Created Successfully', 201);
    }

    public function assignPermission(Role $role, Request $request)
    {
        $valdated = $request->validate([
            'permission_ids' => ['required', 'array', 'exists:permissions,id'],
        ]);
        $role->permissions()->syncWithoutDetaching([...$valdated['permission_ids']]);

        return $this->success(new RoleResource($role->load('permissions')), 'Permissions Assigned Successfully');
    }

    public function removePermission(Role $role, Request $request)
    {
        $valdated = $request->validate([
            'permission_ids' => ['required', 'array', 'exists:permissions,id'],
        ]);
        $role->permissions()->detach($valdated['permission_ids']);

        return $this->success(new RoleResource($role->load('permissions')), 'Permissions Removed Successfully');
    }

    public function index(Request $request)
    {
        $per_page = GetRequestsValidator::validate($request);
        $query = Role::query();
        RequestFilters::apply($query, $request, ['name']);
        $roles = $query->with('permissions')->paginate($per_page);

        return $this->paginate($roles, RoleResource::class, 'Roles Fetched Successfully');
    }

    public function update(Role $role, UpdateRoleRequest $request)
    {
        $validated = $request->validated();
        $role->update($validated);

        return $this->success(new RoleResource($role->load('permissions')), 'Role Updated Successfully');
    }

    public function destroy(Role $role)
    {
        // here should we remove the permmsion from users or not ?
        $role->delete();

        return $this->success(null, 'Role Deleted Successfully');
    }

    public function restore(int $roleId)
    {
        $role = Role::onlyTrashed()->find($roleId);
        if (! $role) {
            return $this->error('Role Not Found', 404);
        }
        $role->restore();

        return $this->success(new RoleResource($role->load('permissions')), 'Role Restored Successfully');
    }

    public function archived(Request $request)
    {
        $per_page = GetRequestsValidator::validate($request);
        $query = Role::query();
        RequestFilters::apply($query, $request, ['name']);
        $roles = $query->onlyTrashed()->with('permissions')->paginate($per_page);

        return $this->paginate($roles, RoleResource::class, 'Archived Roles Fetched Successfully');
    }
}
