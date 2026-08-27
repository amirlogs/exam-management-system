<?php

namespace App\Commit;

use App\Models\Instructor;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Support\Facades\Hash;

class InstructorCommitter
{
    public static function commit(array $data, $importHistory): void
    {
        $user = User::create(
            ['first_name' => $data['first_name'], 'last_name' => $data['last_name'], 'email' => $data['email'], 'password' => Hash::make('password'), 'is_first_login' => true, 'is_active' => true]);
        $role = Role::where('name', 'instructor')->firstOrFail();

        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'assigned_at' => now(),
        ]);

        Instructor::create([
            'user_id' => $user->id,
            'department_id' => $data['department_id'],
            'employee_number' => $data['employee_number'],
            'academic_rank' => $data['academic_rank'],
            'status' => 'active',
        ]);
    }
}
