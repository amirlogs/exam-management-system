<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Instructor;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InstructorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bob = User::where("email", "bob@university.edu")->first();
        $charlie = User::where("email", "charlie@university.edu")->first();

        Instructor::create([
            "user_id" => $bob->id,
            "staff_id" => "INS/2201/01",
            "department_id" => 1, // CS
        ]);

        Instructor::create([
            "user_id" => $charlie->id,
            "staff_id" => "INS/2201/02",
            "department_id" => 2, // Math
        ]);
    }
}
