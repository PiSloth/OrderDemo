<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Department;
use App\Models\CompanyDocument;
use App\Models\CompanyDocumentType;
use App\Models\Training\Training;
use App\Models\Training\TrainingAssignment;
use App\Models\Training\Test;
use App\Models\Training\TestQuestion;
use App\Models\Training\TestOption;
use App\Models\Training\TestAttempt;
use App\Models\Training\TestAnswer;
use Illuminate\Support\Facades\DB;

class CompanyDocumentScorecardSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();
        $adminId = $admin ? $admin->id : 1;

        $storeOpsDept = Department::where('name', 'Store Operations')->first() ?: Department::first();
        $financeDept = Department::where('name', 'Finance & Treasury')->first() ?: Department::first();
        $itDept = Department::where('name', 'IT & Systems')->first() ?: Department::first();

        // Ensure Document Types
        $sopType = CompanyDocumentType::firstOrCreate(['name' => 'Standard Operating Procedure (SOP)']);
        $policyType = CompanyDocumentType::firstOrCreate(['name' => 'Compliance Policy']);
        $manualType = CompanyDocumentType::firstOrCreate(['name' => 'Operational Handbook']);

        // 1. Create realistic Company Documents
        $docCashSop = CompanyDocument::firstOrCreate(
            ['title' => 'SOP-CSH-01: Cash Drawer Balancing & Secure Safe Drop Protocol'],
            [
                'company_document_type_id' => $sopType->id,
                'department_id' => $financeDept->id,
                'announced_at' => now()->subMonths(3),
                'body' => '<p>Standard procedure governing cash handling, register thresholds, and supervisor safe drop sign-offs.</p>',
                'created_by' => $adminId,
            ]
        );

        $docPosPolicy = CompanyDocument::firstOrCreate(
            ['title' => 'POL-POS-04: Cardholder Data Privacy & Terminal Reversal Policy'],
            [
                'company_document_type_id' => $policyType->id,
                'department_id' => $storeOpsDept->id,
                'announced_at' => now()->subMonths(2),
                'body' => '<p>Mandatory policy ensuring payment card security, handling cancelled transactions, and paper receipt retention.</p>',
                'created_by' => $adminId,
            ]
        );

        $docSafetyManual = CompanyDocument::firstOrCreate(
            ['title' => 'MAN-SAF-02: Retail Floor Safety, Ergonomics & Emergency Evacuation'],
            [
                'company_document_type_id' => $manualType->id,
                'department_id' => $storeOpsDept->id,
                'announced_at' => now()->subMonths(4),
                'body' => '<p>Emergency egress routes, slip/trip incident handling, and daily customer floor safety inspection checklists.</p>',
                'created_by' => $adminId,
            ]
        );

        $docAuditSop = CompanyDocument::firstOrCreate(
            ['title' => 'SOP-INV-05: High-Value Asset Tagging & Barcode Scanning Audit'],
            [
                'company_document_type_id' => $sopType->id,
                'department_id' => $storeOpsDept->id,
                'announced_at' => now()->subMonths(1),
                'body' => '<p>Standard audit protocol for jewelry, electronics, barcode verification, and discrepancy escalation.</p>',
                'created_by' => $adminId,
            ]
        );

        // Map documents to existing trainings
        $trainings = Training::all();
        foreach ($trainings as $training) {
            if (str_contains($training->title, 'Floor Safety') || str_contains($training->title, 'Store Operations')) {
                DB::table('training_company_documents')->insertOrIgnore([
                    ['training_id' => $training->id, 'company_document_id' => $docSafetyManual->id, 'created_at' => now(), 'updated_at' => now()],
                    ['training_id' => $training->id, 'company_document_id' => $docCashSop->id, 'created_at' => now(), 'updated_at' => now()],
                ]);
            }
            if (str_contains($training->title, 'Cash Safeguards') || str_contains($training->title, 'Security')) {
                DB::table('training_company_documents')->insertOrIgnore([
                    ['training_id' => $training->id, 'company_document_id' => $docCashSop->id, 'created_at' => now(), 'updated_at' => now()],
                    ['training_id' => $training->id, 'company_document_id' => $docPosPolicy->id, 'created_at' => now(), 'updated_at' => now()],
                ]);
            }
            if (str_contains($training->title, 'POS') || str_contains($training->title, 'Reconciliation')) {
                DB::table('training_company_documents')->insertOrIgnore([
                    ['training_id' => $training->id, 'company_document_id' => $docPosPolicy->id, 'created_at' => now(), 'updated_at' => now()],
                    ['training_id' => $training->id, 'company_document_id' => $docCashSop->id, 'created_at' => now(), 'updated_at' => now()],
                ]);
            }
            if (str_contains($training->title, 'Inventory') || str_contains($training->title, 'Stock Count') || str_contains($training->title, 'Authenticity')) {
                DB::table('training_company_documents')->insertOrIgnore([
                    ['training_id' => $training->id, 'company_document_id' => $docAuditSop->id, 'created_at' => now(), 'updated_at' => now()],
                ]);
            }
        }

        // Link existing test questions to company documents with section references
        $q332 = TestQuestion::find(332);
        if ($q332) {
            $q332->update([
                'company_document_id' => $docCashSop->id,
                'document_section_reference' => 'Section 3.1 - Safe Drop Limits',
                'scope' => 'document',
            ]);
        }

        $q333 = TestQuestion::find(333);
        if ($q333) {
            $q333->update([
                'company_document_id' => $docCashSop->id,
                'document_section_reference' => 'Section 4.2 - Opening Discrepancy Reporting',
                'scope' => 'document',
            ]);
        }

        $q334 = TestQuestion::find(334);
        if ($q334) {
            $q334->update([
                'company_document_id' => $docPosPolicy->id,
                'document_section_reference' => 'Section 2.3 - Reversal Handling',
                'scope' => 'document',
            ]);
        }

        $q335 = TestQuestion::find(335);
        if ($q335) {
            $q335->update([
                'company_document_id' => $docPosPolicy->id,
                'document_section_reference' => 'Section 5.4 - Merchant Slip Archival',
                'scope' => 'document',
            ]);
        }

        $q336 = TestQuestion::find(336);
        if ($q336) {
            $q336->update([
                'company_document_id' => $docAuditSop->id,
                'document_section_reference' => 'Section 1.8 - High-Value Count Cadence',
                'scope' => 'document',
            ]);
        }

        $q337 = TestQuestion::find(337);
        if ($q337) {
            $q337->update([
                'company_document_id' => $docAuditSop->id,
                'document_section_reference' => 'Section 3.2 - Unreadable Barcode Escalation',
                'scope' => 'document',
            ]);
        }

        // Now setup comprehensive questions & answers for Assignment 571 (User: pos@nexgen.com)
        $userPos = User::where('email', 'pos@nexgen.com')->first();
        if ($userPos) {
            $assignment571 = TrainingAssignment::find(571);
            if ($assignment571 && $assignment571->training) {
                $training = $assignment571->training;
                $test = $training->test ?: Test::create([
                    'training_id' => $training->id,
                    'title' => 'Standard Store Operations & Floor Safety Assessment',
                    'description' => 'Comprehensive evaluation of floor safety guidelines and cash protocols.',
                    'passing_score' => 80.0,
                    'attempt_limit' => 3,
                    'status' => 'active',
                ]);

                // Create 4 distinct questions linked to 2 documents:
                // Document A: MAN-SAF-02 (2 questions)
                // Document B: SOP-CSH-01 (2 questions)
                $qData = [
                    [
                        'company_document_id' => $docSafetyManual->id,
                        'document_section_reference' => 'Section 2.1 - Egress & Pathway Clearance',
                        'question' => 'What is the minimum required clearance distance for emergency exit routes on the sales floor?',
                        'question_type' => 'MULTIPLE_CHOICE',
                        'marks' => 25.0,
                        'options' => [
                            ['answer' => '36 inches (approx. 90 cm) unobstructed pathway at all times', 'is_correct' => true],
                            ['answer' => '12 inches along primary displays', 'is_correct' => false],
                            ['answer' => 'Exit corridors may be used for temporary overflow inventory during busy hours', 'is_correct' => false],
                        ],
                    ],
                    [
                        'company_document_id' => $docSafetyManual->id,
                        'document_section_reference' => 'Section 4.3 - Liquid Spills Protocol',
                        'question' => 'When a liquid spill is identified on the customer sales floor, what is the immediate first action?',
                        'question_type' => 'MULTIPLE_CHOICE',
                        'marks' => 25.0,
                        'options' => [
                            ['answer' => 'Place a high-visibility wet floor caution cone and stand by or alert a colleague immediately', 'is_correct' => true],
                            ['answer' => 'Finish existing customer checkout before addressing the spill', 'is_correct' => false],
                            ['answer' => 'Log a maintenance ticket for evening cleanup crew', 'is_correct' => false],
                        ],
                    ],
                    [
                        'company_document_id' => $docCashSop->id,
                        'document_section_reference' => 'Section 1.4 - Register Opening Float Verification',
                        'question' => 'Before starting a shift at the cash terminal, who must sign off on the initial cash drawer float?',
                        'question_type' => 'MULTIPLE_CHOICE',
                        'marks' => 25.0,
                        'options' => [
                            ['answer' => 'Both the assigned cashier and the duty supervisor (dual verification)', 'is_correct' => true],
                            ['answer' => 'Only the cashier without supervisor presence', 'is_correct' => false],
                            ['answer' => 'Any available staff member from the floor', 'is_correct' => false],
                        ],
                    ],
                    [
                        'company_document_id' => $docCashSop->id,
                        'document_section_reference' => 'Section 3.1 - Safe Drop Limits',
                        'question' => 'What is the mandatory threshold triggering an interim cash drop to the safe?',
                        'question_type' => 'MULTIPLE_CHOICE',
                        'marks' => 25.0,
                        'options' => [
                            ['answer' => 'When cash drawer balance exceeds $1,000 in notes', 'is_correct' => true],
                            ['answer' => 'Only at the close of trading hours', 'is_correct' => false],
                            ['answer' => 'Whenever coins exceed 50 units', 'is_correct' => false],
                        ],
                    ],
                ];

                $createdQuestions = [];
                foreach ($qData as $idx => $qd) {
                    $question = TestQuestion::updateOrCreate(
                        [
                            'test_id' => $test->id,
                            'question' => $qd['question'],
                        ],
                        [
                            'training_id' => $training->id,
                            'company_document_id' => $qd['company_document_id'],
                            'document_section_reference' => $qd['document_section_reference'],
                            'question_type' => $qd['question_type'],
                            'marks' => $qd['marks'],
                            'sort_order' => $idx + 1,
                            'scope' => 'document',
                        ]
                    );

                    $correctOption = null;
                    foreach ($qd['options'] as $oIdx => $od) {
                        $opt = TestOption::updateOrCreate(
                            [
                                'test_question_id' => $question->id,
                                'answer' => $od['answer'],
                            ],
                            [
                                'is_correct' => $od['is_correct'],
                                'sort_order' => $oIdx + 1,
                            ]
                        );
                        if ($od['is_correct']) {
                            $correctOption = $opt;
                        }
                    }

                    $createdQuestions[] = [
                        'question' => $question,
                        'correct_option' => $correctOption,
                    ];
                }

                // Now create or update the latest attempt for assignment 571
                $attempt = $assignment571->testAttempts()->latest()->first();
                if (!$attempt) {
                    $attempt = TestAttempt::create([
                        'training_assignment_id' => $assignment571->id,
                        'test_id' => $test->id,
                        'user_id' => $userPos->id,
                        'attempt_number' => 1,
                        'score' => 100.0,
                        'max_score' => 100.0,
                        'percentage' => 100.0,
                        'result' => 'PASSED',
                        'submitted_at' => now()->subDays(5),
                    ]);
                } else {
                    $attempt->update([
                        'test_id' => $test->id,
                        'score' => 100.0,
                        'max_score' => 100.0,
                        'percentage' => 100.0,
                        'result' => 'PASSED',
                    ]);
                }

                // Delete old answers and populate full evaluation transcript
                TestAnswer::where('test_attempt_id', $attempt->id)->delete();
                foreach ($createdQuestions as $cq) {
                    TestAnswer::create([
                        'test_attempt_id' => $attempt->id,
                        'test_question_id' => $cq['question']->id,
                        'selected_option_id' => $cq['correct_option']->id,
                        'is_correct' => true,
                        'marks_obtained' => (float) $cq['question']->marks,
                    ]);
                }
            }

            // Also let's set up Assignment 572 with a mixed score (1 document 100% passed, 1 document 50% needs review)
            $assignment572 = TrainingAssignment::find(572);
            if ($assignment572 && $assignment572->training) {
                $tr2 = $assignment572->training;
                $test2 = $tr2->test ?: Test::create([
                    'training_id' => $tr2->id,
                    'title' => 'POS Terminal & Transaction Reconciliation Assessment',
                    'description' => 'Evaluation for POS terminal handling and dispute compliance.',
                    'passing_score' => 80.0,
                    'attempt_limit' => 3,
                    'status' => 'active',
                ]);

                $qData2 = [
                    // Document 1: Cash SOP (2 questions - both correct -> 100%)
                    [
                        'company_document_id' => $docCashSop->id,
                        'document_section_reference' => 'Section 2.1 - End of Day Float Count',
                        'question' => 'How are discrepancy envelopes labeled during register closing?',
                        'correct' => true,
                        'options' => [
                            ['answer' => 'With register number, date, cashier signature and difference amount', 'is_correct' => true],
                            ['answer' => 'No labeling required if under $5', 'is_correct' => false],
                        ],
                    ],
                    [
                        'company_document_id' => $docCashSop->id,
                        'document_section_reference' => 'Section 3.4 - Safe Drop Receipt Filing',
                        'question' => 'Where must the stamped safe deposit voucher slip be placed?',
                        'correct' => true,
                        'options' => [
                            ['answer' => 'In the designated sealed audit drop box inside the safe room', 'is_correct' => true],
                            ['answer' => 'Discarded after supervisor initials', 'is_correct' => false],
                        ],
                    ],
                    // Document 2: POS Policy (2 questions - 1 correct, 1 incorrect -> 50% - Needs Review)
                    [
                        'company_document_id' => $docPosPolicy->id,
                        'document_section_reference' => 'Section 1.2 - Customer PIN Entry Privacy',
                        'question' => 'What is the required cashier conduct when a customer enters their PIN on the terminal keypad?',
                        'correct' => true,
                        'options' => [
                            ['answer' => 'Turn head away and ensure the privacy shield is facing the cardholder', 'is_correct' => true],
                            ['answer' => 'Observe keypad to assist customer with button presses', 'is_correct' => false],
                        ],
                    ],
                    [
                        'company_document_id' => $docPosPolicy->id,
                        'document_section_reference' => 'Section 4.1 - Storing Customer Credit Card Numbers',
                        'question' => 'Under PCI compliance policy, is it permissible to write down full card numbers on a physical order sheet?',
                        'correct' => false, // incorrect answer selected by user!
                        'options' => [
                            ['answer' => 'Strictly prohibited under all circumstances; only masked numbers allowed', 'is_correct' => true],
                            ['answer' => 'Permissible if locked in supervisor drawer', 'is_correct' => false],
                        ],
                    ],
                ];

                $attempt2 = $assignment572->testAttempts()->latest()->first();
                if (!$attempt2) {
                    $attempt2 = TestAttempt::create([
                        'training_assignment_id' => $assignment572->id,
                        'test_id' => $test2->id,
                        'user_id' => $userPos->id,
                        'attempt_number' => 1,
                        'score' => 75.0,
                        'max_score' => 100.0,
                        'percentage' => 75.0,
                        'result' => 'FAILED',
                        'submitted_at' => now()->subDays(2),
                    ]);
                } else {
                    $attempt2->update([
                        'test_id' => $test2->id,
                        'score' => 75.0,
                        'max_score' => 100.0,
                        'percentage' => 75.0,
                        'result' => 'FAILED',
                    ]);
                }

                TestAnswer::where('test_attempt_id', $attempt2->id)->delete();

                foreach ($qData2 as $i => $item) {
                    $tq = TestQuestion::updateOrCreate(
                        [
                            'test_id' => $test2->id,
                            'question' => $item['question'],
                        ],
                        [
                            'training_id' => $tr2->id,
                            'company_document_id' => $item['company_document_id'],
                            'document_section_reference' => $item['document_section_reference'],
                            'question_type' => 'MULTIPLE_CHOICE',
                            'marks' => 25.0,
                            'sort_order' => $i + 1,
                            'scope' => 'document',
                        ]
                    );

                    $correctOpt = null;
                    $wrongOpt = null;
                    foreach ($item['options'] as $oIdx => $optData) {
                        $to = TestOption::updateOrCreate(
                            [
                                'test_question_id' => $tq->id,
                                'answer' => $optData['answer'],
                            ],
                            [
                                'is_correct' => $optData['is_correct'],
                                'sort_order' => $oIdx + 1,
                            ]
                        );
                        if ($optData['is_correct']) {
                            $correctOpt = $to;
                        } else {
                            $wrongOpt = $to;
                        }
                    }

                    $selectedOpt = $item['correct'] ? $correctOpt : $wrongOpt;
                    TestAnswer::create([
                        'test_attempt_id' => $attempt2->id,
                        'test_question_id' => $tq->id,
                        'selected_option_id' => $selectedOpt->id,
                        'is_correct' => $item['correct'],
                        'marks_obtained' => $item['correct'] ? 25.0 : 0.0,
                    ]);
                }
            }
        }
    }
}
