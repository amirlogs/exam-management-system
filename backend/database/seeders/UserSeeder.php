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
        // database/seeders/UserSeeder.php — replace the $accounts array
        $accounts = [
            ['first_name' => 'Selam',  'last_name' => 'Tesfaye',  'email' => 'superadmin@uems.test',     'roles' => ['super_admin']],
            ['first_name' => 'Meron',  'last_name' => 'Alemu',    'email' => 'uniadmin1@uems.test',      'roles' => ['university_admin']],
            ['first_name' => 'Yonas',  'last_name' => 'Bekele',   'email' => 'uniadmin2@uems.test',      'roles' => ['university_admin']],
            ['first_name' => 'Hana',   'last_name' => 'Girma',    'email' => 'collegeadmin1@uems.test',  'roles' => ['college_admin']],
            ['first_name' => 'Dawit',  'last_name' => 'Solomon',  'email' => 'depthead1@uems.test',      'roles' => ['dept_head']],
            ['first_name' => 'Sara',   'last_name' => 'Mulugeta', 'email' => 'depthead2@uems.test',      'roles' => ['dept_head']],
            ['first_name' => 'Kaleb',  'last_name' => 'Fikru',    'email' => 'examadmin1@uems.test',     'roles' => ['exam_admin']],
            ['first_name' => 'Bethel', 'last_name' => 'Assefa',   'email' => 'leadinstructor1@uems.test', 'roles' => ['lead_instructor']],
            ['first_name' => 'Nathan', 'last_name' => 'Worku',    'email' => 'leadinstructor2@uems.test', 'roles' => ['lead_instructor']],
            ['first_name' => 'Ruth',   'last_name' => 'Haile',    'email' => 'instructor1@uems.test',    'roles' => ['instructor']],
            ['first_name' => 'Amanuel', 'last_name' => 'Getachew', 'email' => 'instructor2@uems.test',    'roles' => ['instructor']],
            ['first_name' => 'Liya',   'last_name' => 'Desta',    'email' => 'instructor3@uems.test',    'roles' => ['instructor']],
            ['first_name' => 'Ahmed',  'last_name' => 'Nur',      'email' => 'multirole1@uems.test',     'roles' => ['instructor', 'dept_head']],
            ['first_name' => 'Bethlehem', 'last_name' => 'Yosef',  'email' => 'student1@uems.test',       'roles' => ['student']],
            ['first_name' => 'Tewodros', 'last_name' => 'Mengistu', 'email' => 'student2@uems.test',       'roles' => ['student']],
            ['first_name' => 'Eden',   'last_name' => 'Kifle',    'email' => 'student3@uems.test',       'roles' => ['student']],
        ];

        foreach ($accounts as $acc) {
            $user = User::updateOrCreate(
                ['email' => $acc['email']],
                [
                    'first_name' => $acc['first_name'],
                    'last_name' => $acc['last_name'],
                    'password' => Hash::make('password'),
                    'is_first_login' => false,
                ]
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
