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
        $roles = ["super_admin", "admin", "dept_head", "instructor", "student"];
        foreach ($roles as $role) {
            User::create([
                "name" => ucfirst(str_replace("_", " ", $role)) . " User",
                "email" => $role . "@test.com",
                "password" => Hash::make("password"),
                "role" => $role,
                "is_first_login" => false,
            ]);
        }
    }
}
