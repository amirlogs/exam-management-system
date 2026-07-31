<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function store(StoreRoleRequest $request)
    {
        $validated = $request->validated();
        $role = Role::create($validated);

        return $this->success($role, 'Role Created Successfully', 201);
    }

    public function assignPermission(Role $role, Request $request)
    {
        $valdated = $request->validate([
            'permission_ids' => ['required', 'array', 'exists:permissions,id'],
        ]);
        $rolePermissions = $role->permissions()->pluck('id')->toArray();
        $role->permissions()->sync([...$valdated['permission_ids'], ...$rolePermissions]);

        return $this->success($role->load('permissions'), 'Permissions Assigned Successfully');
    }

    public function removePermission(Role $role, Request $request)
    {
        $valdated = $request->validate([
            'permission_ids' => ['required', 'array', 'exists:permissions,id'],
        ]);
        $role->permissions()->detach($valdated['permission_ids']);

        return $this->success($role->load('permissions'), 'Permissions Removed Successfully');
    }

    public function index()
    {
        $roles = Role::with('permissions')->get();

        return $this->success(RoleResource::collection($roles), 'Roles Fetched Successfully');
    }

    public function update(Role $role, UpdateRoleRequest $request)
    {
        $validated = $request->validated();

        $role->update($validated);

        return $this->success($role, 'Role Updated Successfully');
    }

    public function destroy(Role $role)
    {
        $role->delete();

        return $this->success(null, 'Role Deleted Successfully');
    }

    public function restore(Role $role)
    {
        $archivedRole = Role::onlyTrashed()->find($role->id);

        if (! $archivedRole) {
            return $this->error('Role Not Found', 404);
        }
        $role->restore();

        return $this->success($role, 'Role Restored Successfully');
    }
}
