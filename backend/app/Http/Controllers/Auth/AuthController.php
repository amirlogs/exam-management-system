<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        // check it the password match
        $user = User::where('email', $data['email'])->first();
        $permissions = $user->permissionNames();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return $this->error('', 'Invalid credentials', 422);
        }

        // first time login
        if ($user->is_first_login) {
            return $this->success(
                [
                    'requires_password_change' => true,
                    'email' => $user->email,
                ],
                'First-time login: Password change required before account activation.',
                200,
            );
        }

        // generate toekn for the user
        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success(
            [
                'token' => $token,
                'user' => new UserResource($user),
                'permissions' => $permissions,
                'workspaces' => $user->workspaces(),
            ],
            'Login successful',
            200,
        );
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $permissions = $user->permissionNames();

        return $this->success(
            [
                'user' => new UserResource($user),
                'permissions' => $permissions,
                'workspaces' => $user->workspaces(),
            ],
            'User data fetched successfully',
            200,
        );
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
        ]);

        $user = $request->user();
        $user->update($data);

        return $this->success(new UserResource($user->refresh()), 'Profile updated successfully');
    }


    public function changePassword(ChangePasswordRequest $request)
    {
        $data = $request->validated();

        // check if the previous password is correct
        $user = Auth::user();
        if (! Hash::check($data['current_password'], $user->password)) {
            return $this->error('', 'Invalid credentials', 422);
        }

        // if so change the password
        $user->password = Hash::make($data['new_password']);
        $user->is_first_login = false;
        $user->save();

        return $this->success(null, 'Password changed successfully', 200);
    }

    public function firstTimePassword(ChangePasswordRequest $request)
    {
        $data = $request->validated();
        if (empty($data['email'])) {
            return $this->error('', 'Email is required.', 422);
        }

        $user = User::where('email', $data['email'])->first();
        if (! $user || ! Hash::check($data['current_password'], $user->password)) {
            return $this->error('', 'Invalid current credentials.', 422);
        }

        if (! $user->is_first_login) {
            return $this->error('', 'First-time password change is not required for this account.', 400);
        }

        $user->password = Hash::make($data['new_password']);
        $user->is_first_login = false;
        $user->save();

        return $this->success(null, 'Password updated successfully. You can now log in with your new password.', 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logout successful', 200);
    }
}
