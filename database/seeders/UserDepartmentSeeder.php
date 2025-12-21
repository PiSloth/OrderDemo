<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserDepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all users
        $users = User::all();

        // Get departments
        $departments = Department::all();

        if ($departments->isEmpty()) {
            $this->command->error('No departments found. Please run DepartmentSeeder first.');
            return;
        }

        // Default department mapping based on position/role
        $positionDepartmentMap = [
            'Super Admin' => 'Management',
            'AGM' => 'Management',
            'Inventory' => 'Operations Department',
            'Purchaser' => 'Operations Department',
            'Branch Supervisor' => 'Sales Department',
            'Guest' => 'Customer Service',
        ];

        foreach ($users as $user) {
            // Check if user already has department
            if (!$user->department_id) {
                // Determine department based on position
                $departmentName = 'IT Department'; // Default department

                if ($user->position && isset($positionDepartmentMap[$user->position->name])) {
                    $departmentName = $positionDepartmentMap[$user->position->name];
                }

                // Find the department
                $department = $departments->where('name', $departmentName)->first();

                if (!$department) {
                    // Fallback to first department if specific one not found
                    $department = $departments->first();
                }

                if ($department) {
                    // Assign department to user
                    $user->update(['department_id' => $department->id]);

                    $this->command->info("Assigned {$user->name} to {$department->name}");
                }
            } else {
                $this->command->info("{$user->name} already has department");
            }
        }
    }
}
