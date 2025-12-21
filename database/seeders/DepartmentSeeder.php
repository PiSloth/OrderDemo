<?php

namespace Database\Seeders;

use App\Models\Department;
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

        // Get the first location as default
        $defaultLocationId = \App\Models\Location::first()->id ?? 1;

        foreach ($departments as $department) {
            Department::create([
                'name' => $department,
                'location_id' => $defaultLocationId,
            ]);
        }
    }
}
