<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChnagePasswordRequest;
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
        $user = User::where("email", $data["email"])->first();

        if (!$user || !Hash::check($data["password"], $user->password)) {
            return $this->error("", "Invalid credentials", 422);
        }
        // generate toekn for the user
        $token = $user->createToken("auth_token")->plainTextToken;

        return $this->success(
            [
                "token" => $token,
                "user" => new UserResource($user),
            ],
            "Login successful",
            200,
        );
    }

    public function changePassword(ChnagePasswordRequest $request)
    {
        $data = $request->validated();
        // check if the previous password is correct
        $user = Auth::user();
        if (!Hash::check($data["current_password"], $user->password)) {
            return $this->error("", "Invalid credentials", 422);
        }

        // if so chnage the password
        $user->password = Hash::make($data["new_password"]);
        $user->save();
        return $this->success(null, "Password changed successfully", 200);
    }

    public function me()
    {
        $user = Auth::user();
        return $this->success(
            new UserResource($user),
            "User data fetched successfully",
            200,
        );
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return $this->success(null, "Logout successful", 200);
    }
}
