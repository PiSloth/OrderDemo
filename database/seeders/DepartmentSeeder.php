<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Location;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            'IT Department',
            'Sales Department',
            'Marketing Department',
            'Finance Department',
            'HR Department',
            'Operations Department',
            'Customer Service',
            'Management',
        ];


        Location::firstOrCreate(['name' => 'Head Office']);

        foreach ($departments as $department) {
            Department::firstOrCreate([
                'name' => $department,
            ]);
        }
    }
}
