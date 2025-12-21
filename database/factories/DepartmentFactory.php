<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Department>
 */
class DepartmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'IT Department',
                'Sales Department',
                'Marketing Department',
                'Finance Department',
                'HR Department',
                'Operations Department',
                'Customer Service',
                'Management',
                'Research & Development',
                'Quality Assurance',
            ]),
        ];
    }
}
