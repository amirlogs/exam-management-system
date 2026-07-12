<?php

namespace App\Http\Controllers;

use App\ApiResponse;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\Concerns\Has;

class AuthController extends Controller
{
    use ApiResponse;
    public function login(LoginRequest $request)
    {
        $data = $request->validated();
        //check it the password match
        $user = User::where("email", $data["email"])->first();

        if (!$user || !Hash::check($data["password"], $user->password)) {
            $this->error()
        }
        // generate toekn for the user

        $token = $user->createToken("auth_token")->plainTextToken;

        return response()->json([
            "message" => "sucessful",
            "data" => [
                "token" => $token,
            ],
        ]);
    }
}
