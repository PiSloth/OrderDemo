<?php

namespace Database\Seeders;

use App\Models\ItSatisfactionSurvey;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ItSatisfactionSurveySeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::today();
        
        ItSatisfactionSurvey::firstOrCreate(
            ['title' => 'IT Department Service Satisfaction Survey'],
            [
                'description' => 'Please rate your overall satisfaction and experience with IT Department services and technical support.',
                'badge_text' => 'IT Satisfaction Survey',
                'start_date' => $today->copy()->format('Y-m-d'), // e.g. 1 Sep
                'end_date' => $today->copy()->addDays(2)->format('Y-m-d'), // e.g. 2-3 Sep
                'is_active' => true,
                'rating_scale' => 5,
                'is_mandatory' => true,
            ]
        );
    }
}
