<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\OfficePosition;
use App\Models\Training\Test;
use App\Models\Training\TestOption;
use App\Models\Training\TestQuestion;
use App\Models\Training\Training;
use App\Models\Training\TrainingAssignment;
use App\Models\Training\TrainingCategory;
use App\Models\Training\TrainingScope;
use App\Models\Training\TrainingSession;
use App\Models\Training\TrainingSessionParticipant;
use App\Models\Training\TrainingTrigger;
use App\Models\User;
use App\Services\Training\TestEvaluationService;
use App\Services\Training\TrainingAssignmentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TrainingSharedSessionAndApprovalTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $trainer;
    protected User $user1;
    protected User $user2;
    protected User $user3;
    protected Training $training;
    protected Test $test;

    protected function setUp(): void
    {
        parent::setUp();

        $dept = Department::create(['name' => 'Operations ' . uniqid()]);
        $pos = OfficePosition::create(['name' => 'Operator']);
        $cat = TrainingCategory::create(['name' => 'SOPs ' . uniqid()]);

        $this->admin = User::factory()->create([
            'email' => 'admin.' . uniqid() . '@example.com',
            'department_id' => $dept->id,
            'office_position_id' => $pos->id,
        ]);
        $this->admin->givePermissionTo([
            'training.catalog.view',
            'training.catalog.update',
            'training.catalog.create',
            'training.attempt.view',
        ]);

        $this->trainer = User::factory()->create([
            'email' => 'trainer.' . uniqid() . '@example.com',
            'department_id' => $dept->id,
        ]);

        $this->user1 = User::factory()->create(['department_id' => $dept->id, 'office_position_id' => $pos->id]);
        $this->user2 = User::factory()->create(['department_id' => $dept->id, 'office_position_id' => $pos->id]);
        $this->user3 = User::factory()->create(['department_id' => $dept->id, 'office_position_id' => $pos->id]);

        $this->training = Training::create([
            'code' => 'SOP-' . uniqid(),
            'title' => 'Machinery Safety SOP',
            'training_category_id' => $cat->id,
            'duration_days' => 3,
            'passing_score' => 80.0,
            'status' => 'active',
        ]);

        TrainingScope::create([
            'training_id' => $this->training->id,
            'department_id' => $dept->id,
            'office_position_id' => $pos->id,
        ]);

        $this->test = Test::create([
            'training_id' => $this->training->id,
            'title' => 'Machinery Safety SOP Assessment',
            'passing_score' => 80.0,
            'attempt_limit' => 3,
            'status' => 'active',
        ]);

        $q = TestQuestion::create([
            'test_id' => $this->test->id,
            'question' => 'Is wearing safety helmet mandatory?',
            'question_type' => 'TRUE_FALSE',
            'marks' => 10.0,
            'sort_order' => 1,
        ]);

        TestOption::create([
            'test_question_id' => $q->id,
            'answer' => 'True',
            'is_correct' => true,
            'sort_order' => 1,
        ]);
        TestOption::create([
            'test_question_id' => $q->id,
            'answer' => 'False',
            'is_correct' => false,
            'sort_order' => 2,
        ]);
    }

    public function test_assigning_training_to_multiple_users_generates_one_shared_session(): void
    {
        $assignmentService = app(TrainingAssignmentService::class);
        $trigger = TrainingTrigger::create([
            'training_id' => $this->training->id,
            'trigger_type' => 'MANUAL',
            'status' => 'ACTIVE',
        ]);

        $assignedCount = $assignmentService->assignCustom(
            training: $this->training,
            trigger: $trigger,
            targetType: 'employees',
            targetIds: [$this->user1->id, $this->user2->id, $this->user3->id],
            assignmentType: 'FULL_TRAINING'
        );

        $this->assertEquals(3, $assignedCount);

        // Verify only 1 TrainingSession was created for this training
        $sessions = TrainingSession::where('training_id', $this->training->id)->get();
        $this->assertCount(1, $sessions);

        $sharedSession = $sessions->first();
        $this->assertEquals(3, $sharedSession->duration_days);
        $this->assertCount(3, $sharedSession->session_dates);
        $this->assertEquals(3, $sharedSession->participants()->count());

        // Verify all 3 users are participants in this shared session
        $participantUserIds = $sharedSession->participants()->pluck('user_id')->all();
        $this->assertContains($this->user1->id, $participantUserIds);
        $this->assertContains($this->user2->id, $participantUserIds);
        $this->assertContains($this->user3->id, $participantUserIds);
    }

    public function test_multi_day_attendance_recorded_with_json_structure(): void
    {
        $session = TrainingSession::create([
            'training_id' => $this->training->id,
            'title' => 'Shared Session 1',
            'session_code' => 'SOP-S001',
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-12',
            'duration_days' => 3,
            'status' => 'OPEN',
        ]);

        $assignment1 = TrainingAssignment::create([
            'training_id' => $this->training->id,
            'user_id' => $this->user1->id,
            'status' => 'PENDING',
        ]);

        $participant = TrainingSessionParticipant::create([
            'training_session_id' => $session->id,
            'training_assignment_id' => $assignment1->id,
            'user_id' => $this->user1->id,
            'attendance_status' => 'REGISTERED',
        ]);

        $dailyJson = [
            '2026-09-10' => ['status' => 'ATTENDED', 'notes' => ''],
            '2026-09-11' => ['status' => 'ABSENT', 'notes' => 'Sick leave'],
            '2026-09-12' => ['status' => 'ATTENDED', 'notes' => ''],
        ];

        $response = $this->actingAs($this->admin)->put("/training/sessions/{$session->id}/attendance", [
            'participants' => [
                [
                    'id' => $participant->id,
                    'attendance_status' => 'ATTENDED',
                    'daily_attendance' => $dailyJson,
                    'notes' => 'Overall attended 2 of 3 days',
                ],
            ],
        ]);

        $response->assertRedirect();

        $participant->refresh();
        $this->assertEquals('ATTENDED', $participant->attendance_status);
        $this->assertIsArray($participant->daily_attendance);
        $this->assertEquals('ABSENT', $participant->daily_attendance['2026-09-11']['status']);
        $this->assertEquals('Sick leave', $participant->daily_attendance['2026-09-11']['notes']);
    }

    public function test_participant_control_and_approval_gates_test_access(): void
    {
        $session = TrainingSession::create([
            'training_id' => $this->training->id,
            'title' => 'Shared Session A',
            'session_code' => 'SOP-S002',
            'duration_days' => 1,
            'status' => 'PENDING',
        ]);

        $assignment1 = TrainingAssignment::create([
            'training_id' => $this->training->id,
            'user_id' => $this->user1->id,
            'assignment_type' => 'FULL_TRAINING',
            'status' => 'PENDING',
        ]);

        $participant1 = TrainingSessionParticipant::create([
            'training_session_id' => $session->id,
            'training_assignment_id' => $assignment1->id,
            'user_id' => $this->user1->id,
            'attendance_status' => 'REGISTERED',
        ]);

        // 1. Employee cannot take test before session approval
        $testResponse = $this->actingAs($this->user1)->get("/training/assignments/{$assignment1->id}/tests/{$this->test->id}/take");
        $testResponse->assertStatus(403);

        // 2. Add User 2 to the session before approval
        $addResponse = $this->actingAs($this->admin)->post("/training/sessions/{$session->id}/participants", [
            'user_id' => $this->user2->id,
        ]);
        $addResponse->assertRedirect();
        $this->assertDatabaseHas('training_session_participants', [
            'training_session_id' => $session->id,
            'user_id' => $this->user2->id,
        ]);

        // 3. Approver approves session
        $approveResponse = $this->actingAs($this->admin)->post("/training/sessions/{$session->id}/approve", [
            'notes' => 'Training completed successfully',
        ]);
        $approveResponse->assertRedirect();

        $session->refresh();
        $this->assertNotNull($session->approved_at);
        $this->assertEquals($this->admin->id, $session->approved_by);

        // 4. Now employee can access test
        $unlockResponse = $this->actingAs($this->user1)->get("/training/assignments/{$assignment1->id}/tests/{$this->test->id}/take");
        $unlockResponse->assertStatus(200);
    }

    public function test_failed_trainees_are_auto_assigned_to_shared_remedial_session_referencing_parent(): void
    {
        $session = TrainingSession::create([
            'training_id' => $this->training->id,
            'title' => 'Machinery Safety - Session #1',
            'session_code' => 'SOP-S001',
            'duration_days' => 1,
            'status' => 'OPEN',
            'approved_at' => now(),
            'approved_by' => $this->admin->id,
        ]);

        $assignment1 = TrainingAssignment::create([
            'training_id' => $this->training->id,
            'user_id' => $this->user1->id,
            'status' => 'IN_PROGRESS',
        ]);
        $participant1 = TrainingSessionParticipant::create([
            'training_session_id' => $session->id,
            'training_assignment_id' => $assignment1->id,
            'user_id' => $this->user1->id,
            'attendance_status' => 'ATTENDED',
        ]);

        $assignment2 = TrainingAssignment::create([
            'training_id' => $this->training->id,
            'user_id' => $this->user2->id,
            'status' => 'IN_PROGRESS',
        ]);
        $participant2 = TrainingSessionParticipant::create([
            'training_session_id' => $session->id,
            'training_assignment_id' => $assignment2->id,
            'user_id' => $this->user2->id,
            'attendance_status' => 'ATTENDED',
        ]);

        $evalService = app(TestEvaluationService::class);
        $wrongAnswer = $this->test->questions->first()->options->where('is_correct', false)->first();

        // User 1 fails test
        $attempt1 = $evalService->startAttempt($this->test, $this->user1, $assignment1, $session->id);
        $evalService->submitAttempt($attempt1, [
            $this->test->questions->first()->id => $wrongAnswer->id,
        ]);

        // User 2 fails test
        $attempt2 = $evalService->startAttempt($this->test, $this->user2, $assignment2, $session->id);
        $evalService->submitAttempt($attempt2, [
            $this->test->questions->first()->id => $wrongAnswer->id,
        ]);

        // Check that exactly ONE shared Remedial Session was created with parent reference
        $remedialSessions = TrainingSession::where('parent_session_id', $session->id)->get();
        $this->assertCount(1, $remedialSessions);

        $remSession = $remedialSessions->first();
        $this->assertEquals($session->id, $remSession->parent_session_id);
        $this->assertStringContainsString('SOP-S001-REM', $remSession->session_code);
        $this->assertStringContainsString('Ref: SOP-S001', $remSession->title);

        // Check that BOTH failed trainees were assigned to this same remedial session
        $this->assertCount(2, $remSession->participants);
        $remUserIds = $remSession->participants->pluck('user_id')->all();
        $this->assertContains($this->user1->id, $remUserIds);
        $this->assertContains($this->user2->id, $remUserIds);
    }

    public function test_training_session_permissions_enforcement(): void
    {
        $unauthorizedUser = User::factory()->create();

        // 1. Cannot view sessions without read permission
        $resp = $this->actingAs($unauthorizedUser)->get('/training/sessions');
        $resp->assertStatus(403);

        // 2. Grant read permission
        $unauthorizedUser->givePermissionTo('training-session.read');
        $resp = $this->actingAs($unauthorizedUser)->get('/training/sessions');
        $resp->assertStatus(200);

        // 3. Cannot create session without create permission
        $createResp = $this->actingAs($unauthorizedUser)->post('/training/sessions', [
            'training_id' => $this->training->id,
            'title' => 'Test Session',
            'status' => 'OPEN',
        ]);
        $createResp->assertStatus(403);

        // 4. Grant create permission
        $unauthorizedUser->givePermissionTo('training-session.create');
        $createResp = $this->actingAs($unauthorizedUser)->post('/training/sessions', [
            'training_id' => $this->training->id,
            'title' => 'Test Session',
            'status' => 'OPEN',
        ]);
        $createResp->assertRedirect();
    }

    public function test_deleting_session_deletes_related_generated_test_attempts_and_remedial_sessions(): void
    {
        $session = TrainingSession::create([
            'training_id' => $this->training->id,
            'title' => 'Session To Delete',
            'session_code' => 'DEL-S001',
            'duration_days' => 1,
            'status' => 'OPEN',
            'approved_at' => now(),
            'approved_by' => $this->admin->id,
        ]);

        $assignment = TrainingAssignment::create([
            'training_id' => $this->training->id,
            'user_id' => $this->user1->id,
            'status' => 'IN_PROGRESS',
        ]);

        $participant = TrainingSessionParticipant::create([
            'training_session_id' => $session->id,
            'training_assignment_id' => $assignment->id,
            'user_id' => $this->user1->id,
            'attendance_status' => 'ATTENDED',
        ]);

        // Create a child remedial session
        $remedial = TrainingSession::create([
            'training_id' => $this->training->id,
            'parent_session_id' => $session->id,
            'title' => 'Remedial DEL',
            'session_code' => 'DEL-S001-REM-01',
            'duration_days' => 1,
            'status' => 'PENDING',
        ]);

        // Generate a test attempt related to this session and submit it (creates attempt result)
        $evalService = app(TestEvaluationService::class);
        $attempt = $evalService->startAttempt($this->test, $this->user1, $assignment, $session->id);
        $evalService->submitAttempt($attempt, [
            $this->test->questions->first()->id => $this->test->questions->first()->options->first()->id,
        ]);

        $this->assertDatabaseHas('test_attempts', ['training_session_id' => $session->id]);
        $this->assertDatabaseHas('training_session_participants', ['training_session_id' => $session->id]);
        $this->assertDatabaseHas('training_sessions', ['id' => $remedial->id]);

        // User without delete permission cannot delete
        $unauth = User::factory()->create();
        $failResp = $this->actingAs($unauth)->delete("/training/sessions/{$session->id}");
        $failResp->assertStatus(403);

        // Admin with delete permission attempts to delete session with attempt results -> BLOCKED
        $this->admin->givePermissionTo('training-session.delete');
        $blockedResp = $this->actingAs($this->admin)->delete("/training/sessions/{$session->id}");
        $blockedResp->assertSessionHasErrors(['message']);
        $this->assertDatabaseHas('training_sessions', ['id' => $session->id]);

        // Create a new session with participants and unattempted template (no submitted results)
        $cleanSession = TrainingSession::create([
            'training_id' => $this->training->id,
            'title' => 'Clean Session To Delete',
            'session_code' => 'CLN-S001',
            'duration_days' => 1,
            'status' => 'OPEN',
            'approved_at' => now(),
            'approved_by' => $this->admin->id,
        ]);

        TrainingSessionParticipant::create([
            'training_session_id' => $cleanSession->id,
            'training_assignment_id' => $assignment->id,
            'user_id' => $this->user1->id,
            'attendance_status' => 'ATTENDED',
        ]);

        // Admin deletes clean session without attempt results -> SUCCESS
        $delResp = $this->actingAs($this->admin)->delete("/training/sessions/{$cleanSession->id}");
        $delResp->assertRedirect();
        $this->assertDatabaseMissing('training_sessions', ['id' => $cleanSession->id]);
        $this->assertDatabaseMissing('training_session_participants', ['training_session_id' => $cleanSession->id]);
    }

    public function test_compliance_matrix_assignment_deletion_and_orphaned_cleanup(): void
    {
        $this->admin->givePermissionTo('training-session.delete');

        // Create an orphaned pending assignment with no session participants and no test attempts
        $orphanAssignment = TrainingAssignment::create([
            'training_id' => $this->training->id,
            'user_id' => $this->user2->id,
            'status' => 'PENDING',
        ]);

        $this->assertDatabaseHas('training_assignments', ['id' => $orphanAssignment->id]);

        // 1. Delete single assignment via DELETE /training/assignments/{assignment}
        $resp = $this->actingAs($this->admin)->delete("/training/assignments/{$orphanAssignment->id}");
        $resp->assertRedirect();
        $this->assertDatabaseMissing('training_assignments', ['id' => $orphanAssignment->id]);

        // 2. Test bulk cleanup of orphaned assignments
        $orphan1 = TrainingAssignment::create([
            'training_id' => $this->training->id,
            'user_id' => $this->user1->id,
            'status' => 'PENDING',
        ]);
        $orphan2 = TrainingAssignment::create([
            'training_id' => $this->training->id,
            'user_id' => $this->user2->id,
            'status' => 'IN_PROGRESS',
        ]);

        $cleanupResp = $this->actingAs($this->admin)->post('/training/assignments/cleanup-orphaned');
        $cleanupResp->assertRedirect();
        $this->assertDatabaseMissing('training_assignments', ['id' => $orphan1->id]);
        $this->assertDatabaseMissing('training_assignments', ['id' => $orphan2->id]);
    }
}

