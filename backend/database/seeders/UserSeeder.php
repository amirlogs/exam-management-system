<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@university.edu',
            'password' => Hash::make('password'),
            'is_first_login' => false,
        ]);

        User::create([
            'name' => 'Admin User',
            'email' => 'admin@university.edu',
            'password' => Hash::make('password'),
            'is_first_login' => false,
        ]);

        User::create([
            'name' => 'Dr. Alice (Dept Head)',
            'email' => 'alice@university.edu',
            'password' => Hash::make('password'),
            'is_first_login' => false,
        ]);

        User::create([
            'name' => 'Dr. Bob (Lead Instructor)',
            'email' => 'bob@university.edu',
            'password' => Hash::make('password'),
            'is_first_login' => false,
        ]);

        User::create([
            'name' => 'Dr. Charlie (Instructor)',
            'email' => 'charlie@university.edu',
            'password' => Hash::make('password'),
            'is_first_login' => false,
        ]);

        User::create([
            'name' => 'Amir (Student)',
            'email' => 'amir@university.edu',
            'password' => Hash::make('password'),
            'is_first_login' => true,
        ]);

        User::create([
            'name' => 'Zara (Student)',
            'email' => 'zara@university.edu',
            'password' => Hash::make('password'),
            'is_first_login' => true,
        ]);
    }
}