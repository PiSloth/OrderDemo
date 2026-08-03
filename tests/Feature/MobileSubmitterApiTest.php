<?php

namespace Tests\Feature;

use App\Models\Kpi\KpiTaskInstance;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MobileSubmitterApiTest extends TestCase
{
    use DatabaseTransactions;

    private function createTestUser(array $overrides = []): User
    {
        $position = \App\Models\Position::firstOrCreate(['name' => 'Staff']);
        $branch = \App\Models\Branch::firstOrCreate(['name' => 'Main Branch']);

        return User::factory()->create(array_merge([
            'position_id' => $position->id,
            'branch_id' => $branch->id,
        ], $overrides));
    }

    private function createTestTaskInstance(User $user): KpiTaskInstance
    {
        $group = \App\Models\Kpi\KpiGroup::firstOrCreate([
            'name' => 'Daily Operations',
        ]);

        $template = \App\Models\Kpi\KpiTaskTemplate::firstOrCreate([
            'title' => 'Daily Maintenance Inspection',
        ], [
            'slug' => 'daily-maintenance-inspection-' . uniqid(),
            'frequency' => 'daily',
            'kpi_group_id' => $group->id,
            'created_by_user_id' => $user->id,
            'requires_images' => true,
        ]);



        $assignment = \App\Models\Kpi\KpiTaskAssignment::firstOrCreate([
            'task_template_id' => $template->id,
            'user_id' => $user->id,
        ]);


        return KpiTaskInstance::create([
            'task_assignment_id' => $assignment->id,
            'task_template_id' => $template->id,
            'kpi_group_id' => $group->id,
            'user_id' => $user->id,
            'period_type' => 'daily',
            'period_start' => now()->toDateString(),
            'period_end' => now()->toDateString(),
            'due_at' => now()->addHours(5),
            'status' => 'pending',
        ]);
    }


    public function test_user_can_login_via_mobile_api(): void
    {
        $user = $this->createTestUser([
            'email' => 'submitter@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/mobile/login', [
            'email' => 'submitter@example.com',
            'password' => 'password123',
            'device_name' => 'Test Phone',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'token',
                'user' => ['id', 'name', 'email'],
            ]);
    }

    public function test_submitter_can_fetch_assigned_tasks(): void
    {
        $user = $this->createTestUser();
        $task = $this->createTestTaskInstance($user);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/mobile/tasks');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $task->id,
            ]);
    }

    public function test_submitter_can_submit_task_with_evidence_photo(): void
    {
        Storage::fake('public');

        $user = $this->createTestUser();
        $task = $this->createTestTaskInstance($user);

        $fakePhoto = UploadedFile::fake()->image('evidence.jpg', 1920, 1080);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/mobile/tasks/{$task->id}/submit", [
                'remark' => 'Inspected and verified working clean condition.',
                'images' => [$fakePhoto],
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Task submitted successfully.',
            ]);



        $this->assertDatabaseHas('kpi_task_instances', [
            'id' => $task->id,
            'status' => 'pending_approval',
        ]);

        $this->assertDatabaseHas('kpi_task_submissions', [
            'task_instance_id' => $task->id,
            'submitted_by_user_id' => $user->id,
            'employee_remark' => 'Inspected and verified working clean condition.',
        ]);
    }

}

