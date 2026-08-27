<?php

namespace App\Commit;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserCommitter
{
    public static function commit(array $data, $importHistory): void
    {
        User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => Hash::make('password'),
            'is_first_login' => true,
            'is_active' => true,
        ]);
    }
}
