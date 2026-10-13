<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Training\TrainingAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrainingScorecardController extends Controller
{
    public function show(Request $request, ?TrainingAssignment $assignment = null): Response
    {
        $currentUser = $request->user();

        // If no specific assignment is passed, pick the first or from query
        if (!$assignment || !$assignment->exists) {
            $assignmentId = $request->query('assignment_id');
            if ($assignmentId) {
                $assignment = TrainingAssignment::findOrFail($assignmentId);
            } else {
                $assignment = TrainingAssignment::query()
                    ->when(!$currentUser->can('training.catalog.view'), function ($q) use ($currentUser) {
                        $q->where('user_id', $currentUser->id);
                    })
                    ->whereHas('testAttempts')
                    ->latest('updated_at')
                    ->first();

                if (!$assignment) {
                    $assignment = TrainingAssignment::query()
                        ->when(!$currentUser->can('training.catalog.view'), function ($q) use ($currentUser) {
                            $q->where('user_id', $currentUser->id);
                        })
                        ->latest('id')
                        ->firstOrFail();
                }
            }
        }

        // Authorization check: User can view their own, or supervisor with permission
        if ($assignment->user_id !== $currentUser->id && !$currentUser->can('training.catalog.view')) {
            abort(403, 'Unauthorized to view this training scorecard.');
        }

        $assignment->load([
            'training.category',
            'training.companyDocuments.type',
            'training.test.questions.options',
            'training.test.questions.companyDocument.type',
            'user.department',
            'user.officePosition',
            'sessionParticipants.session.trainer',
            'testAttempts' => fn($q) => $q->with([
                'answers.selectedOption',
                'answers.question.options',
                'answers.question.companyDocument.type',
            ])->latest(),
        ]);

        $latestAttempt = $assignment->testAttempts->first();
        $previousAttempts = $assignment->testAttempts;

        // Calculate International Practice Scoring Metrics
        $score = $latestAttempt ? (float) $latestAttempt->score : 0.0;
        $maxScore = $latestAttempt ? (float) $latestAttempt->max_score : 0.0;
        $percentage = $latestAttempt ? (float) $latestAttempt->percentage : 0.0;
        $passingScore = (float) ($assignment->training->passing_score ?? 80.0);

        // International Grade & Honors Scale (ISO 29993 / Kirkpatrick Level 2 Standard)
        if ($percentage >= 90.0) {
            $grade = 'A+';
            $gradeTitle = 'Distinction (Mastery)';
            $gradeBadgeColor = 'emerald';
            $competencyLevel = 'Level 4: Master Practitioner';
            $performanceDescriptor = 'Exceeds company document and operational benchmarks with distinction.';
        } elseif ($percentage >= 80.0) {
            $grade = 'A';
            $gradeTitle = 'Merit (Proficient)';
            $gradeBadgeColor = 'blue';
            $competencyLevel = 'Level 3: Proficient Practitioner';
            $performanceDescriptor = 'Demonstrates strong operational proficiency and high compliance comprehension across company documents.';
        } elseif ($percentage >= 70.0) {
            $grade = 'B';
            $gradeTitle = 'Pass (Competent)';
            $gradeBadgeColor = 'teal';
            $competencyLevel = 'Level 2: Qualified Operational';
            $performanceDescriptor = 'Meets standard corporate qualification and document procedure criteria.';
        } elseif ($percentage >= 60.0) {
            $grade = 'C';
            $gradeTitle = 'Marginal Pass (Needs Improvement)';
            $gradeBadgeColor = 'amber';
            $competencyLevel = 'Level 1: Developing';
            $performanceDescriptor = 'Meets minimum passing margin; review of non-proficient company documents recommended.';
        } else {
            $grade = 'F';
            $gradeTitle = 'Unsatisfactory (Retraining Required)';
            $gradeBadgeColor = 'rose';
            $competencyLevel = 'Uncertified (Requires Retake)';
            $performanceDescriptor = 'Did not achieve minimum passing benchmark; review required documents and retake examination.';
        }

        // Compute question breakdown & accuracy
        $answers = $latestAttempt ? $latestAttempt->answers : collect();
        $totalQuestions = $answers->count();
        $correctQuestions = $answers->where('is_correct', true)->count();
        $incorrectQuestions = $totalQuestions - $correctQuestions;
        $accuracyRate = $totalQuestions > 0 ? round(($correctQuestions / $totalQuestions) * 100, 1) : 0.0;

        // Breakdown by question type (Multi-Select, Multiple Choice, True/False)
        $typeBreakdown = [];
        foreach ($answers as $ans) {
            $qType = $ans->question->question_type ?? 'MULTIPLE_CHOICE';
            if (!isset($typeBreakdown[$qType])) {
                $typeBreakdown[$qType] = ['total' => 0, 'correct' => 0, 'marks_obtained' => 0, 'max_marks' => 0];
            }
            $typeBreakdown[$qType]['total']++;
            if ($ans->is_correct) {
                $typeBreakdown[$qType]['correct']++;
            }
            $typeBreakdown[$qType]['marks_obtained'] += (float) $ans->marks_obtained;
            $typeBreakdown[$qType]['max_marks'] += (float) ($ans->question->marks ?? 0);
        }

        // Company Document Mastery & Pass Rate Breakdown
        $docGroups = [];
        if ($answers->isNotEmpty()) {
            foreach ($answers as $ans) {
                $question = $ans->question;
                $doc = $question?->companyDocument;
                $docKey = $doc ? ('doc_' . $doc->id) : 'general';

                if (!isset($docGroups[$docKey])) {
                    $docGroups[$docKey] = [
                        'document_id' => $doc?->id,
                        'document_title' => $doc?->title ?? 'General Operational & Standard Knowledge',
                        'document_type' => $doc?->type?->name ?? 'Standard Curriculum',
                        'document_code' => $doc ? ('DOC-' . str_pad($doc->id, 3, '0', STR_PAD_LEFT)) : 'GEN-01',
                        'sections' => [],
                        'total_questions' => 0,
                        'correct_questions' => 0,
                        'marks_obtained' => 0.0,
                        'max_marks' => 0.0,
                    ];
                }

                $docGroups[$docKey]['total_questions']++;
                if ($ans->is_correct) {
                    $docGroups[$docKey]['correct_questions']++;
                }
                $docGroups[$docKey]['marks_obtained'] += (float) ($ans->marks_obtained ?? 0);
                $docGroups[$docKey]['max_marks'] += (float) ($question?->marks ?? 1.0);

                if (!empty($question?->document_section_reference) && !in_array($question->document_section_reference, $docGroups[$docKey]['sections'])) {
                    $docGroups[$docKey]['sections'][] = $question->document_section_reference;
                }
            }
        } elseif ($assignment->training && $assignment->training->test && $assignment->training->test->questions->isNotEmpty()) {
            foreach ($assignment->training->test->questions as $q) {
                $doc = $q->companyDocument;
                $docKey = $doc ? ('doc_' . $doc->id) : 'general';

                if (!isset($docGroups[$docKey])) {
                    $docGroups[$docKey] = [
                        'document_id' => $doc?->id,
                        'document_title' => $doc?->title ?? 'General Operational & Standard Knowledge',
                        'document_type' => $doc?->type?->name ?? 'Standard Curriculum',
                        'document_code' => $doc ? ('DOC-' . str_pad($doc->id, 3, '0', STR_PAD_LEFT)) : 'GEN-01',
                        'sections' => [],
                        'total_questions' => 0,
                        'correct_questions' => 0,
                        'marks_obtained' => 0.0,
                        'max_marks' => 0.0,
                    ];
                }

                $docGroups[$docKey]['total_questions']++;
                $docGroups[$docKey]['max_marks'] += (float) ($q->marks ?? 1.0);
                if (!empty($q->document_section_reference) && !in_array($q->document_section_reference, $docGroups[$docKey]['sections'])) {
                    $docGroups[$docKey]['sections'][] = $q->document_section_reference;
                }
            }

            foreach ($docGroups as &$g) {
                $g['marks_obtained'] = round(($g['max_marks'] * $percentage) / 100, 1);
                $g['correct_questions'] = (int) round(($g['total_questions'] * $percentage) / 100);
            }
            unset($g);
        }

        $documentBreakdown = [];
        foreach ($docGroups as $group) {
            $maxM = $group['max_marks'];
            $totalQ = $group['total_questions'];
            $correctQ = $group['correct_questions'];

            if ($maxM > 0) {
                $docPct = round(($group['marks_obtained'] / $maxM) * 100, 1);
            } elseif ($totalQ > 0) {
                $docPct = round(($correctQ / $totalQ) * 100, 1);
            } else {
                $docPct = $percentage;
            }

            $isPassed = $docPct >= $passingScore;

            $documentBreakdown[] = [
                'document_id' => $group['document_id'],
                'document_title' => $group['document_title'],
                'document_type' => $group['document_type'],
                'document_code' => $group['document_code'],
                'sections' => $group['sections'],
                'sections_text' => !empty($group['sections']) ? implode(', ', $group['sections']) : null,
                'total_questions' => $totalQ,
                'correct_questions' => $correctQ,
                'marks_obtained' => round($group['marks_obtained'], 1),
                'max_marks' => round($maxM, 1),
                'percentage' => $docPct,
                'benchmark' => $passingScore,
                'is_passed' => $isPassed,
                'status' => $isPassed ? 'Passed' : 'Needs Review',
            ];
        }

        // Include any attached training documents that did not have specific test questions
        if ($assignment->training && $assignment->training->companyDocuments) {
            foreach ($assignment->training->companyDocuments as $doc) {
                $docKey = 'doc_' . $doc->id;
                if (!isset($docGroups[$docKey])) {
                    $documentBreakdown[] = [
                        'document_id' => $doc->id,
                        'document_title' => $doc->title,
                        'document_type' => $doc->type?->name ?? 'Reference Material',
                        'document_code' => 'DOC-' . str_pad($doc->id, 3, '0', STR_PAD_LEFT),
                        'sections' => [],
                        'sections_text' => 'Reading & Reference Curriculum Material',
                        'total_questions' => 0,
                        'correct_questions' => 0,
                        'marks_obtained' => 0.0,
                        'max_marks' => 0.0,
                        'percentage' => $percentage > 0 ? $percentage : 100.0,
                        'benchmark' => $passingScore,
                        'is_passed' => ($percentage > 0 ? $percentage : 100.0) >= $passingScore,
                        'status' => 'Curriculum Material',
                    ];
                }
            }
        }

        // Keep competencyDomains mapped from document breakdown for compatibility
        $competencyDomains = array_map(function ($doc) {
            return [
                'domain' => $doc['document_title'],
                'description' => ($doc['sections_text'] ? "Focus: {$doc['sections_text']} • " : '') . "{$doc['correct_questions']}/{$doc['total_questions']} questions passed ({$doc['marks_obtained']}/{$doc['max_marks']} marks)",
                'score' => $doc['percentage'],
                'benchmark' => $doc['benchmark'],
            ];
        }, $documentBreakdown);

        // Unique Certificate / Transcript Verification Code
        $issueDate = $assignment->completed_at ?? ($latestAttempt ? $latestAttempt->submitted_at : now());
        $verificationCode = 'TRN-' . strtoupper(substr(md5($assignment->id . '-' . $assignment->created_at), 0, 8)) . '-' . date('Ymd', strtotime($issueDate));

        // Calculate Expiry / Next Renewal Date
        $retrainInterval = (int) ($assignment->training->retrain_interval ?? 12);
        $retrainUnit = $assignment->training->retrain_unit ?? 'month';
        $expiryDate = null;
        if ($assignment->completed_at) {
            $expiryDate = date('Y-m-d', strtotime("+{$retrainInterval} {$retrainUnit}s", strtotime($assignment->completed_at)));
        }

        // Trainer / Proctor Information
        $attendedSession = $assignment->sessionParticipants
            ->firstWhere('attendance_status', 'ATTENDED')?->session
            ?? $assignment->sessionParticipants->first()?->session;

        $trainer = $attendedSession?->trainer;

        // Users for avatar-switch modal (managers only): latest assessed assignment per employee
        $scorecardUsers = [];
        $scorecardDepartments = [];
        if ($currentUser->can('training.catalog.view')) {
            $scorecardUsers = TrainingAssignment::query()
                ->with(['user.department', 'user.officePosition', 'user.branch'])
                ->whereHas('testAttempts')
                ->latest('updated_at')
                ->take(100)
                ->get()
                ->filter(fn($a) => $a->user)
                ->unique('user_id')
                ->take(50)
                ->values()
                ->map(fn($a) => [
                    'id' => $a->user->id,
                    'name' => $a->user->name,
                    'email' => $a->user->email,
                    'department_id' => $a->user->department_id,
                    'department' => $a->user->department ? ['id' => $a->user->department->id, 'name' => $a->user->department->name] : null,
                    'branch' => $a->user->branch ? ['id' => $a->user->branch->id, 'name' => $a->user->branch->name] : null,
                    'office_position' => $a->user->officePosition ? ['id' => $a->user->officePosition->id, 'name' => $a->user->officePosition->name] : null,
                    'position' => $a->user->officePosition ? ['name' => $a->user->officePosition->name] : null,
                    'profile_photo_url' => $a->user->profile_photo_url,
                    'latest_assignment_id' => $a->id,
                ])->values()->all();
            $scorecardDepartments = Department::orderBy('name')->get(['id', 'name']);
        }

        // Expose avatar URL for the displayed candidate
        $assignment->user?->append('profile_photo_url');

        return Inertia::render('Training/Scorecard', [
            'assignment' => $assignment,
            'latestAttempt' => $latestAttempt,
            'previousAttempts' => $previousAttempts,
            'grading' => [
                'score' => $score,
                'max_score' => $maxScore,
                'percentage' => $percentage,
                'passing_score' => $passingScore,
                'passed' => $percentage >= $passingScore,
                'grade' => $grade,
                'grade_title' => $gradeTitle,
                'grade_badge_color' => $gradeBadgeColor,
                'competency_level' => $competencyLevel,
                'performance_descriptor' => $performanceDescriptor,
                'evaluation_framework' => 'Kirkpatrick Level 2 (Knowledge Acquisition & Procedural Competency)',
                'standard_code' => 'ISO 29993 / Standardized Assessment Rubric',
                'verification_code' => $verificationCode,
                'issue_date' => $issueDate ? date('F j, Y', strtotime($issueDate)) : 'In Progress',
                'expiry_date' => $expiryDate ? date('F j, Y', strtotime($expiryDate)) : 'N/A',
                'accuracy_rate' => $accuracyRate,
                'total_questions' => $totalQuestions,
                'correct_questions' => $correctQuestions,
                'incorrect_questions' => $incorrectQuestions,
                'type_breakdown' => $typeBreakdown,
                'competency_domains' => $competencyDomains,
                'document_breakdown' => $documentBreakdown,
            ],
            'trainer' => $trainer,
            'attendedSession' => $attendedSession,
            'scorecardUsers' => $scorecardUsers,
            'scorecardDepartments' => $scorecardDepartments,
            'currentUserName' => $currentUser->name,
        ]);
    }
}
