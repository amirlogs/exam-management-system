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
        $instructorUser = User::where(
            "email",
            "instructor@test.com",
        )->firstOrFail();
        $cs = Department::where("name", "Computer Science")->firstOrFail();

        Instructor::create([
            "user_id" => $instructorUser->id,
            "staff_id" => "INS/001/17",
            "home_department_id" => $cs->id,
        ]);
    }
}
