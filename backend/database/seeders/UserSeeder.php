<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // Global
            ['email' => 'superadmin@uems.test',    'roles' => ['super_admin']],

            // University-scoped (set university_id later via role-assign API)
            ['email' => 'uniadmin1@uems.test',      'roles' => ['university_admin']],
            ['email' => 'uniadmin2@uems.test',      'roles' => ['university_admin']],

            // College-scoped
            ['email' => 'collegeadmin1@uems.test',  'roles' => ['college_admin']],

            // Department-scoped
            ['email' => 'depthead1@uems.test',      'roles' => ['dept_head']],
            ['email' => 'depthead2@uems.test',      'roles' => ['dept_head']],

            // University- or department-scoped (optional role — pick one mode when you assign it)
            ['email' => 'examadmin1@uems.test',     'roles' => ['exam_admin']],

            // Course-offering-scoped via course_instructors, not user_roles
            ['email' => 'leadinstructor1@uems.test', 'roles' => ['lead_instructor']],
            ['email' => 'leadinstructor2@uems.test', 'roles' => ['lead_instructor']],
            ['email' => 'instructor1@uems.test',    'roles' => ['instructor']],
            ['email' => 'instructor2@uems.test',    'roles' => ['instructor']],
            ['email' => 'instructor3@uems.test',    'roles' => ['instructor']],

            // Multi-role user — mirrors the real-world case (one person, two responsibilities)
            ['email' => 'multirole1@uems.test',     'roles' => ['instructor', 'dept_head']],

            // Own-scope only
            ['email' => 'student1@uems.test',       'roles' => ['student']],
            ['email' => 'student2@uems.test',       'roles' => ['student']],
            ['email' => 'student3@uems.test',       'roles' => ['student']],
        ];

        foreach ($accounts as $acc) {
            $user = User::updateOrCreate(
                ['email' => $acc['email']],
                ['password' => Hash::make('password'), 'is_first_login' => false]
            );

            foreach ($acc['roles'] as $roleName) {
                $role = Role::where('name', $roleName)->firstOrFail();

                UserRole::updateOrCreate(
                    ['user_id' => $user->id, 'role_id' => $role->id],
                    ['assigned_at' => now()]
                );
            }
        }
    }
}
