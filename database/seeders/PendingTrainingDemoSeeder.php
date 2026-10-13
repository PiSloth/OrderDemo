<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Training\TrainingCategory;
use App\Models\Training\Training;
use App\Models\Training\Test;
use App\Models\Training\TestQuestion;
use App\Models\Training\TestOption;
use App\Models\Training\TrainingSession;
use App\Models\Training\TrainingSessionParticipant;
use App\Models\Training\TrainingAssignment;
use App\Models\Training\TrainingTrigger;
use Carbon\Carbon;

class PendingTrainingDemoSeeder extends Seeder
{
    /**
     * Run the database seeds for pending training sessions and task cases for pos@nexgen.com.
     */
    public function run(): void
    {
        $targetUser = User::where('email', 'pos@nexgen.com')->first();
        if (!$targetUser) {
            $this->command->error('User with email pos@nexgen.com not found!');
            return;
        }

        // Pick a trainer user (different from pos@nexgen.com)
        $trainer = User::where('id', '!=', $targetUser->id)->where('suspended', false)->first() ?: $targetUser;

        // Categories
        $catOperations = TrainingCategory::firstOrCreate(
            ['name' => 'Store Operations & Security'],
            ['description' => 'Protocols for store safety, asset protection, and operational continuity.']
        );
        $catTechnical = TrainingCategory::firstOrCreate(
            ['name' => 'POS & Terminal Systems'],
            ['description' => 'Point of sale hardware, transaction flows, and dispute handling.']
        );
        $catCompliance = TrainingCategory::firstOrCreate(
            ['name' => 'Audit & Compliance'],
            ['description' => 'Regulatory inventory audits and stock reconciliations.']
        );
        $catWorkflow = TrainingCategory::firstOrCreate(
            ['name' => 'Operational Workflows'],
            ['description' => 'System updates and standard operating procedure revisions.']
        );

        // =========================================================================
        // CASE 1: Full Training with Upcoming Session (Test Locked - Awaiting Session Approval)
        // =========================================================================
        $training1 = Training::firstOrCreate(
            ['code' => 'TR-SEC-602'],
            [
                'title' => 'Store Security, Cash Safeguards & Anti-Theft Protocol',
                'description' => 'Mandatory hands-on session on store register locking, register drawer balances, and theft deterrence.',
                'training_category_id' => $catOperations->id,
                'passing_score' => 85.0,
                'duration_days' => 14,
                'retrain_interval' => 6,
                'retrain_unit' => 'months',
                'status' => 'ACTIVE',
            ]
        );

        $test1 = Test::firstOrCreate(
            ['training_id' => $training1->id],
            [
                'title' => 'Store Security & Cash Protocol Assessment',
                'description' => 'Knowledge evaluation for security procedures.',
                'passing_score' => 85.0,
                'attempt_limit' => 3,
                'status' => 'active',
            ]
        );
        $this->attachSampleQuestions($test1, [
            [
                'question' => 'What is the required threshold for initiating a cash drop from the primary register?',
                'options' => [
                    ['answer' => 'Whenever cash balance exceeds $1,000 or the designated safe limit', 'is_correct' => true],
                    ['answer' => 'Only at the end of the shift regardless of cash accumulation', 'is_correct' => false],
                    ['answer' => 'Whenever a customer pays with high denomination banknotes', 'is_correct' => false],
                    ['answer' => 'Cash drops are optional for supervisors', 'is_correct' => false],
                ],
            ],
            [
                'question' => 'Who must be notified immediately in the event of an inventory discrepancy at opening?',
                'options' => [
                    ['answer' => 'Duty Store Manager and Internal Audit Compliance Officer', 'is_correct' => true],
                    ['answer' => 'The previous shift employee only', 'is_correct' => false],
                    ['answer' => 'No one until the end of the week audit', 'is_correct' => false],
                ],
            ],
        ]);

        $assignment1 = TrainingAssignment::updateOrCreate(
            [
                'training_id' => $training1->id,
                'user_id' => $targetUser->id,
            ],
            [
                'assignment_type' => 'FULL_TRAINING',
                'due_date' => Carbon::now()->addDays(14)->toDateString(),
                'status' => 'PENDING',
                'completed_at' => null,
            ]
        );

        $session1 = TrainingSession::updateOrCreate(
            ['session_code' => 'SES-SEC-2026-01'],
            [
                'training_id' => $training1->id,
                'trainer_id' => $trainer->id,
                'title' => 'In-Person Store Security & Cash Handling Workshop - Batch A',
                'scheduled_at' => Carbon::now()->addDays(2)->setTime(10, 0),
                'start_date' => Carbon::now()->addDays(2)->toDateString(),
                'end_date' => Carbon::now()->addDays(2)->toDateString(),
                'duration_days' => 1,
                'venue' => 'Main Branch Training Room (Room 204)',
                'meeting_link' => null,
                'status' => 'OPEN',
                'approved_by' => null,
                'approved_at' => null,
            ]
        );

        TrainingSessionParticipant::updateOrCreate(
            [
                'training_session_id' => $session1->id,
                'training_assignment_id' => $assignment1->id,
                'user_id' => $targetUser->id,
            ],
            [
                'attendance_status' => 'REGISTERED',
                'attended_at' => null,
                'notes' => 'Registered for morning session.',
            ]
        );

        // =========================================================================
        // CASE 2: Full Training with Approved Session (Attendance Verified, "Take Test" UNLOCKED!)
        // =========================================================================
        $training2 = Training::firstOrCreate(
            ['code' => 'TR-POS-305'],
            [
                'title' => 'Advanced POS Terminal & Credit Card Dispute Protocol',
                'description' => 'Comprehensive guidance on handling chargebacks, EMV chip fallbacks, and multi-currency reconciliation on POS terminals.',
                'training_category_id' => $catTechnical->id,
                'passing_score' => 80.0,
                'duration_days' => 10,
                'retrain_interval' => 6,
                'retrain_unit' => 'months',
                'status' => 'ACTIVE',
            ]
        );

        $test2 = Test::firstOrCreate(
            ['training_id' => $training2->id],
            [
                'title' => 'POS Terminal & Dispute Protocol Examination',
                'description' => 'Assessment on chargeback policies and terminal error codes.',
                'passing_score' => 80.0,
                'attempt_limit' => 3,
                'status' => 'active',
            ]
        );
        $this->attachSampleQuestions($test2, [
            [
                'question' => 'How should a cashier handle a terminal message saying "TRANSACTION REVERSED - RE-INSERT"?',
                'options' => [
                    ['answer' => 'Cancel transaction, wait for slip printout, and re-attempt card insertion once', 'is_correct' => true],
                    ['answer' => 'Ask the customer to pay with another method immediately without checking', 'is_correct' => false],
                    ['answer' => 'Manually type card number into register terminal', 'is_correct' => false],
                ],
            ],
            [
                'question' => 'Where are signed merchant transaction receipts stored after end-of-day settlement?',
                'options' => [
                    ['answer' => 'Locked in the branch settlement safe in date-stamped envelopes for 180 days', 'is_correct' => true],
                    ['answer' => 'Discarded after 24 hours if no disputes are filed', 'is_correct' => false],
                    ['answer' => 'Kept at the checkout register table indefinitely', 'is_correct' => false],
                ],
            ],
        ]);

        $assignment2 = TrainingAssignment::updateOrCreate(
            [
                'training_id' => $training2->id,
                'user_id' => $targetUser->id,
            ],
            [
                'assignment_type' => 'FULL_TRAINING',
                'due_date' => Carbon::now()->addDays(10)->toDateString(),
                'status' => 'IN_PROGRESS',
                'completed_at' => null,
            ]
        );

        $session2 = TrainingSession::updateOrCreate(
            ['session_code' => 'SES-POS-2026-02'],
            [
                'training_id' => $training2->id,
                'trainer_id' => $trainer->id,
                'title' => 'Advanced POS Operations & Customer Dispute Handling Workshop',
                'scheduled_at' => Carbon::now()->subDays(1)->setTime(14, 0),
                'start_date' => Carbon::now()->subDays(1)->toDateString(),
                'end_date' => Carbon::now()->subDays(1)->toDateString(),
                'duration_days' => 1,
                'venue' => 'Virtual Session (Zoom Meeting)',
                'meeting_link' => 'https://meet.nexgen.com/pos-dispute',
                'status' => 'COMPLETED',
                'approved_by' => $trainer->id,
                'approved_at' => Carbon::now()->subDays(1)->setTime(16, 30),
                'approval_notes' => 'All enrolled participants attended and actively participated in terminal exercises.',
            ]
        );

        TrainingSessionParticipant::updateOrCreate(
            [
                'training_session_id' => $session2->id,
                'training_assignment_id' => $assignment2->id,
                'user_id' => $targetUser->id,
            ],
            [
                'attendance_status' => 'ATTENDED',
                'attended_at' => Carbon::now()->subDays(1)->setTime(14, 5),
                'notes' => 'Attended virtual workshop, verified by trainer.',
            ]
        );

        // =========================================================================
        // CASE 3: TEST ONLY Case (Attendance Waived, Pending Assessment Task)
        // =========================================================================
        $training3 = Training::firstOrCreate(
            ['code' => 'TR-AUD-410'],
            [
                'title' => 'Q4 Annual Inventory Auditing & Stock Count Evaluation',
                'description' => 'Fast-track test-only qualification for quarterly stock count procedures and barcode discrepancy tracking.',
                'training_category_id' => $catCompliance->id,
                'passing_score' => 80.0,
                'duration_days' => 7,
                'retrain_interval' => 3,
                'retrain_unit' => 'months',
                'status' => 'ACTIVE',
            ]
        );

        $test3 = Test::firstOrCreate(
            ['training_id' => $training3->id],
            [
                'title' => 'Q4 Annual Inventory Auditing Test',
                'description' => 'Quarterly certification test for stock audit.',
                'passing_score' => 80.0,
                'attempt_limit' => 3,
                'status' => 'active',
            ]
        );
        $this->attachSampleQuestions($test3, [
            [
                'question' => 'How frequently must high-value jewelry display cabinets undergo visual count reconciliations?',
                'options' => [
                    ['answer' => 'Twice daily (Opening and Closing shifts)', 'is_correct' => true],
                    ['answer' => 'Once per calendar week', 'is_correct' => false],
                    ['answer' => 'Only during quarterly physical inventory', 'is_correct' => false],
                ],
            ],
            [
                'question' => 'When a barcode tag fails to scan during a physical audit, what is the mandatory immediate action?',
                'options' => [
                    ['answer' => 'Segregate item in the quarantine tray, log SKU manually into audit discrepancy log', 'is_correct' => true],
                    ['answer' => 'Print a random barcode tag to complete the batch', 'is_correct' => false],
                    ['answer' => 'Skip the item and continue counting', 'is_correct' => false],
                ],
            ],
        ]);

        $assignment3 = TrainingAssignment::updateOrCreate(
            [
                'training_id' => $training3->id,
                'user_id' => $targetUser->id,
            ],
            [
                'assignment_type' => 'TEST_ONLY',
                'due_date' => Carbon::now()->addDays(7)->toDateString(),
                'status' => 'PENDING',
                'completed_at' => null,
            ]
        );

        // =========================================================================
        // CASE 4: Workflow Update Retraining with Trigger Reason (Pending Assessment)
        // =========================================================================
        $training4 = Training::firstOrCreate(
            ['code' => 'TR-WF-502'],
            [
                'title' => 'Order Processing & Fulfillment Workflow 2.0',
                'description' => 'Orientation on newly launched cross-branch fulfillment pipeline and digital dispatch manifests.',
                'training_category_id' => $catWorkflow->id,
                'passing_score' => 80.0,
                'duration_days' => 21,
                'retrain_interval' => 12,
                'retrain_unit' => 'months',
                'status' => 'ACTIVE',
            ]
        );

        $trigger4 = TrainingTrigger::firstOrCreate(
            [
                'training_id' => $training4->id,
                'trigger_type' => 'WORKFLOW_CHANGE',
            ],
            [
                'reason' => 'Mandatory retraining: System-wide deployment of Order Fulfillment Workflow 2.0 across all retail branches.',
                'status' => 'ACTIVE',
                'created_by' => $trainer->id,
            ]
        );

        $test4 = Test::firstOrCreate(
            ['training_id' => $training4->id],
            [
                'title' => 'Order Processing Workflow 2.0 Test',
                'description' => 'Assessment on workflow transitions and dispatch logs.',
                'passing_score' => 80.0,
                'attempt_limit' => 3,
                'status' => 'active',
            ]
        );
        $this->attachSampleQuestions($test4, [
            [
                'question' => 'In Workflow 2.0, what status must an order be transitioned to before shipping manifests can be generated?',
                'options' => [
                    ['answer' => 'READY_FOR_DISPATCH after quality control verification', 'is_correct' => true],
                    ['answer' => 'PENDING_APPROVAL', 'is_correct' => false],
                    ['answer' => 'DRAFT', 'is_correct' => false],
                ],
            ],
        ]);

        $assignment4 = TrainingAssignment::updateOrCreate(
            [
                'training_id' => $training4->id,
                'user_id' => $targetUser->id,
            ],
            [
                'training_trigger_id' => $trigger4->id,
                'assignment_type' => 'FULL_TRAINING',
                'due_date' => Carbon::now()->addDays(21)->toDateString(),
                'status' => 'PENDING',
                'completed_at' => null,
            ]
        );

        $session4 = TrainingSession::updateOrCreate(
            ['session_code' => 'SES-WF-2026-03'],
            [
                'training_id' => $training4->id,
                'trainer_id' => $trainer->id,
                'title' => 'Order Processing Workflow 2.0 Live Walkthrough & Q&A',
                'scheduled_at' => Carbon::now()->addDays(5)->setTime(11, 0),
                'start_date' => Carbon::now()->addDays(5)->toDateString(),
                'end_date' => Carbon::now()->addDays(5)->toDateString(),
                'duration_days' => 1,
                'venue' => 'Headquarters Conference Hall B (Hybrid Video)',
                'meeting_link' => 'https://meet.nexgen.com/workflow-v2',
                'status' => 'OPEN',
                'approved_by' => null,
                'approved_at' => null,
            ]
        );

        TrainingSessionParticipant::updateOrCreate(
            [
                'training_session_id' => $session4->id,
                'training_assignment_id' => $assignment4->id,
                'user_id' => $targetUser->id,
            ],
            [
                'attendance_status' => 'REGISTERED',
                'attended_at' => null,
                'notes' => 'Enrolled in hybrid walkthrough.',
            ]
        );

        echo "Successfully created 4 diverse pending training scenarios for {$targetUser->name} ({$targetUser->email})!\n";
    }

    /**
     * Helper to create questions and options for a test.
     */
    protected function attachSampleQuestions(Test $test, array $questionDefs): void
    {
        foreach ($questionDefs as $idx => $qDef) {
            $question = TestQuestion::firstOrCreate(
                [
                    'test_id' => $test->id,
                    'question' => $qDef['question'],
                ],
                [
                    'training_id' => $test->training_id,
                    'scope' => 'catalog',
                    'question_type' => 'MULTIPLE_CHOICE',
                    'marks' => 10.0,
                    'sort_order' => $idx + 1,
                ]
            );

            foreach ($qDef['options'] as $oIdx => $opt) {
                TestOption::firstOrCreate(
                    [
                        'test_question_id' => $question->id,
                        'answer' => $opt['answer'],
                    ],
                    [
                        'is_correct' => $opt['is_correct'],
                        'sort_order' => $oIdx + 1,
                    ]
                );
            }
        }
    }
}
