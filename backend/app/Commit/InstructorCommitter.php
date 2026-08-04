<?php

namespace App\Commit;

use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class InstructorCommitter
{
    public static function commit(array $data, array $context = []): void
    {
        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => Hash::make(Str::random(12)),
            'is_first_login' => true,
        ]);

        $role = Role::where('name', 'instructor')->first();

        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'assigned_at' => now(),
        ]);
    }
}
