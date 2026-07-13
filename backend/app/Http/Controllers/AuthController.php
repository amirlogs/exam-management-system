<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $data = $request->validated();
        //check it the password match
        $user = User::whee("email", $data["email"])->first();

        if (!$user || !Hash::check($data["password"], $user->password)) {
            $this->error("", "Invalid credentials", 401);
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
}
