<?php

namespace App\Commit;

use App\Mail\UserRegistered;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserCommitter
{
    public static function commit(array $data, $importHistory = null): User
    {
        $userData = isset($data[0]) && is_array($data[0]) ? $data[0] : $data;
        $plainPassword = $userData['password'] ?? ('Exam-' . strtoupper(Str::random(6)));

        $user = User::create([
            'first_name' => $userData['first_name'],
            'last_name' => $userData['last_name'],
            'email' => $userData['email'],
            'password' => Hash::make($plainPassword),
            'is_first_login' => true,
            'is_active' => true,
        ]);

        Mail::to($user->email)->queue(new UserRegistered($user, $plainPassword));
        return $user;
    }
}
