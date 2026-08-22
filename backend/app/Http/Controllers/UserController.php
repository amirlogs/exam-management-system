<?php

namespace App\Http\Controllers;

use App\Http\Filters\RequestFilters;
use App\Http\Requests\AssignRoleRequest;
use App\Http\Requests\SetWorkspaceRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\UserRole;
use App\Services\Navigation\NavigationService;
use App\Validation\GetRequestsValidator;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();
        $user = User::create($validated);

        return $this->success(new UserResource($user->refresh()), 'User created successfully', 201);
    }

    public function assignRole(User $user, AssignRoleRequest $request)
    {
        $validated = $request->validated();
        $userRoles = $user->userRoles()->pluck('role_id');
        $duplicate = $user->userRoles()
            ->where('role_id', $validated['role_id'])
            ->where('university_id', $validated['university_id'] ?? null)
            ->where('college_id', $validated['college_id'] ?? null)
            ->where('department_id', $validated['department_id'] ?? null)
            ->exists();

        if ($duplicate) {
            return $this->error('This exact role assignment already exists for this user', 400);
        }

        // if ($userRoles->contains($validated['role_id'])) {
        //     return $this->error('Role already assigned to user', 400);
        // }

        $user->userRoles()->create([
            ...$validated,
            'assigned_by' => $request->user()->id,
            'assigned_at' => now(),
        ]);

        return $this->success(new UserResource($user->refresh()), 'Role assigned successfully');
    }

    public function removeRole(User $user, Request $request)
    {
        // userRole
        $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);
        $userRole = UserRole::where('user_id', $user->id)->where('role_id', $request->role_id)->first();
        if (! $userRole) {
            return $this->error('Role not assigned to user', 400);
        }

        $userRole->delete();

        return $this->success(new UserResource($user->refresh()), 'Role removed successfully');
    }

    public function index(Request $request)
    {
        $per_page = GetRequestsValidator::validate($request);
        $query = User::query();
        RequestFilters::apply($query, $request, ['first_name', 'email']);
        $user = $query->with('userRoles.role')->paginate($per_page);

        return $this->paginate($user, UserResource::class, 'User fetched successfully');
    }

    public function update(User $user, UpdateUserRequest $request)
    {
        $validated = $request->validated();
        $user->update($validated);

        return $this->success(new UserResource($user->refresh()), 'User updated successfully');

    }

    public function disable(User $user)
    {
        $user->update(['is_active' => false]);

        return $this->success(new UserResource($user->refresh()), 'User disabled successfully');
    }
    public function activate(User $user)
    {
        $user->update(['is_active' => true]);

        return $this->success(new UserResource($user->refresh()), 'User activated successfully');
    }


    public function allowedRoutes(SetWorkspaceRequest $request, NavigationService $navigationService)
    {
        $validated =  $request->validated();
        if (! $request->user()->hasWorkspace($validated['workspace'])) {
            return $this->error(null, 'You are not allowed to access this workspace', 403);
        }
        $navigation = $navigationService->forUser($request->user(), $validated['workspace']);

        return $this->success($navigation, 'Navigation fetched successfully');
    }

    public function setWorkspace(SetWorkspaceRequest $request)
    {
        $validated = $request->validated();

        if (! $request->user()->hasWorkspace($request->workspace)) {
            return $this->error(null, 'You are not allowed to access this workspace', 403);
        }
        
        $request->user()->update(['default_workspace' => $validated['workspace']]);

        return $this->success(new UserResource($request->user()->refresh()), 'Workspace set successfully');
    }
}
