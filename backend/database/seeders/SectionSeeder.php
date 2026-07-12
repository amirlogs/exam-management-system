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
        $cs = Department::where("name", "Computer Science")->firstOrFail();

        Section::create([
            "home_department_id" => $cs->id,
            "year_level" => 2,
            "section_label" => "A",
        ]);
    }
}
