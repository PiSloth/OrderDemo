<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Training\TrainingCategory;
use App\Models\Training\Training;
use App\Models\Training\Test;
use App\Models\Training\TrainingAssignment;
use App\Models\Training\TestAttempt;
use Carbon\Carbon;

class TrainingSkillMatrixSampleSeeder extends Seeder
{
    /**
     * Run the database seeds for sample skill matrix data.
     */
    public function run(): void
    {
        $users = User::where('suspended', false)->get();
        if ($users->isEmpty()) {
            return;
        }

        // 1. Categories
        $categories = [
            'Core Operations' => 'Essential daily retail and customer operations skills',
            'Technical & POS' => 'Digital system mastery, transaction management and POS compliance',
            'Quality & Compliance' => 'Quality standards, auditing protocols and regulatory guidelines',
            'Leadership & Communication' => 'Interpersonal excellence, crisis handling and guidance',
        ];

        $catModels = [];
        foreach ($categories as $catName => $desc) {
            $catModels[$catName] = TrainingCategory::firstOrCreate(
                ['name' => $catName],
                ['description' => $desc]
            );
        }

        // 2. Training Modules
        $modulesData = [
            [
                'code' => 'TR-OPS-101',
                'title' => 'Standard Store Operations & Floor Safety',
                'category' => 'Core Operations',
                'passing_score' => 80.0,
            ],
            [
                'code' => 'TR-POS-201',
                'title' => 'POS Terminal & Transaction Reconciliation',
                'category' => 'Technical & POS',
                'passing_score' => 80.0,
            ],
            [
                'code' => 'TR-QAC-301',
                'title' => 'Product Authenticity & Inspection Audit',
                'category' => 'Quality & Compliance',
                'passing_score' => 85.0,
            ],
            [
                'code' => 'TR-CS-401',
                'title' => 'Customer Consultation & Dispute Resolution',
                'category' => 'Leadership & Communication',
                'passing_score' => 80.0,
            ],
            [
                'code' => 'TR-SEC-501',
                'title' => 'Digital Asset Security & Access Protocol',
                'category' => 'Quality & Compliance',
                'passing_score' => 90.0,
            ],
        ];

        $trainings = [];
        foreach ($modulesData as $m) {
            $cat = $catModels[$m['category']];
            $t = Training::firstOrCreate(
                ['code' => $m['code']],
                [
                    'title' => $m['title'],
                    'description' => $m['title'] . ' competency certification.',
                    'training_category_id' => $cat->id,
                    'passing_score' => $m['passing_score'],
                    'duration_days' => 14,
                    'retrain_interval' => 6,
                    'retrain_unit' => 'months',
                    'status' => 'ACTIVE',
                ]
            );
            $trainings[] = $t;

            // Ensure matching Test exists
            Test::firstOrCreate(
                ['training_id' => $t->id],
                [
                    'title' => $t->title . ' Evaluation Test',
                    'description' => 'Comprehensive evaluation for ' . $t->title,
                    'passing_score' => $t->passing_score,
                    'attempt_limit' => 3,
                    'status' => 'active',
                ]
            );
        }

        // 3. Assign to users with varied realistic performance profiles
        // User 0: Star Performer (90%+ across all)
        // User 1: Dependable Specialist (80-88%)
        // User 2: Competent Contributor (70-82%)
        // Others: Mixed or Developing
        $profileScores = [
            0 => [95.0, 92.0, 96.0, 94.0, 90.0], // Star person!
            1 => [88.0, 85.0, 92.0, 84.0, 86.0], // Dependable Specialist
            2 => [82.0, 78.0, 85.0, 75.0, 80.0], // Competent
            3 => [91.0, 94.0, 88.0, 92.0, 95.0], // High performer
            4 => [70.0, 65.0, 75.0, 60.0, null], // Developing
        ];

        foreach ($users as $uIndex => $user) {
            $scores = $profileScores[$uIndex % count($profileScores)] ?? [85.0, 80.0, 82.0, null, null];

            foreach ($trainings as $tIndex => $training) {
                $score = $scores[$tIndex] ?? null;
                $test = $training->tests()->first();

                $assignment = TrainingAssignment::firstOrCreate(
                    [
                        'training_id' => $training->id,
                        'user_id' => $user->id,
                    ],
                    [
                        'assignment_type' => 'FULL_TRAINING',
                        'due_date' => Carbon::now()->addDays(15),
                        'status' => $score !== null && $score >= $training->passing_score ? 'COMPLETED' : ($score !== null ? 'IN_PROGRESS' : 'PENDING'),
                        'completed_at' => $score !== null && $score >= $training->passing_score ? Carbon::now()->subDays(rand(1, 10)) : null,
                    ]
                );

                if ($score !== null && $test) {
                    $isPassed = $score >= (float) $training->passing_score;
                    TestAttempt::firstOrCreate(
                        [
                            'test_id' => $test->id,
                            'user_id' => $user->id,
                            'training_assignment_id' => $assignment->id,
                        ],
                        [
                            'attempt_number' => 1,
                            'started_at' => Carbon::now()->subDays(rand(2, 10)),
                            'submitted_at' => Carbon::now()->subDays(rand(1, 9)),
                            'score' => $score,
                            'max_score' => 100.0,
                            'percentage' => $score,
                            'result' => $isPassed ? 'PASSED' : 'FAILED',
                        ]
                    );
                }
            }
        }
    }
}
