<?php

namespace Database\Seeders;

use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $studentUser = User::where("email", "student@test.com")->firstOrFail();
        $section = Section::where("section_label", "A")->firstOrFail();

        Student::create([
            "user_id" => $studentUser->id,
            "university_id" => "ugr/001/17",
            "section_id" => $section->id,
        ]);
    }
}
