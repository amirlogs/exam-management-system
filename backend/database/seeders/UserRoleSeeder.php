<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdmin = User::where(
            "email",
            "superadmin@university.edu",
        )->first();
        $admin = User::where("email", "admin@university.edu")->first();
        $alice = User::where("email", "alice@university.edu")->first();
        $bob = User::where("email", "bob@university.edu")->first();
        $charlie = User::where("email", "charlie@university.edu")->first();
        $amir = User::where("email", "amir@university.edu")->first();
        $zara = User::where("email", "zara@university.edu")->first();

        $csDept = 1; // Computer Science
        $mathDept = 2; // Mathematics

        // Super Admin - global (no department)
        UserRole::create([
            "user_id" => $superAdmin->id,
            "role_id" => Role::where("name", "super_admin")->first()->id,
            "department_id" => null,
            "assigned_by" => $superAdmin->id,
            "assigned_at" => now(),
        ]);

        // Admin - global (no department)
        UserRole::create([
            "user_id" => $admin->id,
            "role_id" => Role::where("name", "admin")->first()->id,
            "department_id" => null,
            "assigned_by" => $superAdmin->id,
            "assigned_at" => now(),
        ]);

        // Dr. Alice - Dept Head of CS
        UserRole::create([
            "user_id" => $alice->id,
            "role_id" => Role::where("name", "dept_head")->first()->id,
            "department_id" => $csDept,
            "assigned_by" => $superAdmin->id,
            "assigned_at" => now(),
        ]);

        // Dr. Bob - Lead Instructor in CS
        UserRole::create([
            "user_id" => $bob->id,
            "role_id" => Role::where("name", "lead_instructor")->first()->id,
            "department_id" => $csDept,
            "assigned_by" => $alice->id,
            "assigned_at" => now(),
        ]);

        // Dr. Charlie - Instructor in Math
        UserRole::create([
            "user_id" => $charlie->id,
            "role_id" => Role::where("name", "instructor")->first()->id,
            "department_id" => $mathDept,
            "assigned_by" => $superAdmin->id,
            "assigned_at" => now(),
        ]);

        // Amir - Student (gets added via SectionSeeder/StudentSeeder)
        // Zara - Student (gets added via SectionSeeder/StudentSeeder)
    }
}
