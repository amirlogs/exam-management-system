<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::create([
            'name' => 'super_admin',
            'description' => 'Full system access',
        ]);

        Role::create([
            'name' => 'admin',
            'description' => 'System administrator',
        ]);

        Role::create([
            'name' => 'dept_head',
            'description' => 'Department head',
        ]);

        Role::create([
            'name' => 'lead_instructor',
            'description' => 'Lead instructor for a course',
        ]);

        Role::create([
            'name' => 'instructor',
            'description' => 'Instructor',
        ]);

        Role::create([
            'name' => 'student',
            'description' => 'Student',
        ]);
        Role::firstOrCreate(['name' => 'developer'], [
            'description' => 'Full access — development/testing only, not for production use',
        ]);
    }
}
