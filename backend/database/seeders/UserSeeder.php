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
            [
                'first_name' => 'Selam',
                'last_name' => 'Tesfaye',
                'email' => 'superadmin@uems.test',
                'roles' => ['super_admin'],
            ],

            [
                'first_name' => 'Meron',
                'last_name' => 'Alemu',
                'email' => 'universityadmin@uems.test',
                'roles' => ['university_admin'],
            ],

            [
                'first_name' => 'Hana',
                'last_name' => 'Girma',
                'email' => 'collegeadmin@uems.test',
                'roles' => ['college_admin'],
            ],

            [
                'first_name' => 'Dawit',
                'last_name' => 'Solomon',
                'email' => 'depthead.se@uems.test',
                'roles' => ['dept_head'],
            ],

            [
                'first_name' => 'Sara',
                'last_name' => 'Mulugeta',
                'email' => 'depthead.ee@uems.test',
                'roles' => ['dept_head'],
            ],

            [
                'first_name' => 'Kaleb',
                'last_name' => 'Fikru',
                'email' => 'examadmin@uems.test',
                'roles' => ['exam_admin'],
            ],

            [
                'first_name' => 'Bethel',
                'last_name' => 'Assefa',
                'email' => 'instructor.se1@uems.test',
                'roles' => ['lead_instructor'],
            ],

            [
                'first_name' => 'Nathan',
                'last_name' => 'Worku',
                'email' => 'instructor.cs1@uems.test',
                'roles' => ['lead_instructor'],
            ],

            [
                'first_name' => 'Ruth',
                'last_name' => 'Haile',
                'email' => 'instructor.ee1@uems.test',
                'roles' => ['instructor'],
            ],

            [
                'first_name' => 'Amanuel',
                'last_name' => 'Getachew',
                'email' => 'instructor.se2@uems.test',
                'roles' => ['instructor'],
            ],
            [
                'first_name' => 'Liya',
                'last_name' => 'Desta',
                'email' => 'instructor.cs2@uems.test',
                'roles' => ['instructor'],
            ],
            [
                'first_name' => 'Ahmed',
                'last_name' => 'Nur',
                'email' => 'multirole@uems.test',
                'roles' => [
                    'instructor',
                    'dept_head',
                ],
            ],
            [
                'first_name' => 'Bethlehem',
                'last_name' => 'Yosef',
                'email' => 'student.se1@uems.test',
                'roles' => ['student'],
            ],
            [
                'first_name' => 'Tewodros',
                'last_name' => 'Mengistu',
                'email' => 'student.se2@uems.test',
                'roles' => ['student'],
            ],
            [
                'first_name' => 'Eden',
                'last_name' => 'Kifle',
                'email' => 'student.cs1@uems.test',
                'roles' => ['student'],
            ],
            [
                'first_name' => 'Ruth',
                'last_name' => 'Bekele',
                'email' => 'student.ee1@uems.test',
                'roles' => ['student'],
            ],
        ];

        foreach ($accounts as $account) {
            $user = User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'first_name' => $account['first_name'],
                    'last_name' => $account['last_name'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'is_first_login' => false,
                ]
            );

            foreach ($account['roles'] as $roleName) {
                $role = Role::where('name', $roleName)->firstOrFail();

                UserRole::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'role_id' => $role->id,
                    ],
                    [
                        'assigned_at' => now(),
                    ]
                );
            }
        }
    }
}
