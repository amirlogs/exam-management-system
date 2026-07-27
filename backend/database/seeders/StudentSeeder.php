<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $amir = User::where("email", "amir@university.edu")->first();
        $zara = User::where("email", "zara@university.edu")->first();
        $superAdmin = User::where(
            "email",
            "superadmin@university.edu",
        )->first();

        Student::create([
            "user_id" => $amir->id,
            "university_id" => "ugr/348309/34",
            "section_id" => 1, // CS Year 2 Section A
        ]);

        Student::create([
            "user_id" => $zara->id,
            "university_id" => "ugr/348310/35",
            "section_id" => 2, // Math Year 1 Section B
        ]);

        // Assign student roles to Amir and Zara
        UserRole::create([
            "user_id" => $amir->id,
            "role_id" => Role::where("name", "student")->first()->id,
            "department_id" => null,
            "assigned_by" => $superAdmin->id,
            "assigned_at" => now(),
        ]);

        UserRole::create([
            "user_id" => $zara->id,
            "role_id" => Role::where("name", "student")->first()->id,
            "department_id" => null,
            "assigned_by" => $superAdmin->id,
            "assigned_at" => now(),
        ]);
    }
}
