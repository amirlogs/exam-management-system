<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Section;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Section::create([
            "home_department_id" => 1, // CS
            "year_level" => 2,
            "section_label" => "A",
        ]);

        Section::create([
            "home_department_id" => 2, // Math
            "year_level" => 1,
            "section_label" => "B",
        ]);
    }
}
