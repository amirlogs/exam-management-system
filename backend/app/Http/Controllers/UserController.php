<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignRoleRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\UserRole;

class UserController extends Controller
{
    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();
        $user = User::create($validated);

        return $this->success($user->refresh(), 'User created successfully', 201);
    }

    public function assignRole(User $user, AssignRoleRequest $request)
    {
        $validated = $request->validated();
        $userRoles = $user->userRoles()->pluck('role_id');

        if ($userRoles->contains($validated['role_id'])) {
            return $this->error('Role already assigned to user', 400);
        }

        $user->userRoles()->create([
            ...$validated,
            'assigned_by' => $request->user()->id,
            'assigned_at' => now(),
        ]);

        return $this->success($user->refresh(), 'Role assigned successfully');
    }

    public function removeRole(User $user, UserRole $userRole)
    {
        $userRole->delete();

        return $this->success($user->refresh(), 'Role removed successfully');
    }

    public function index()
    {
        $user = User::with('userRoles.role')->get();

        return $this->success(UserResource::collection($user), 'User fetched successfully');
    }

    public function update(User $user, UpdateUserRequest $request)
    {
        $validated = $request->validated();
        $user->update($validated);

        return $this->success($user->refresh(), 'User updated successfully');
    }
}
