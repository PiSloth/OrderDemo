<?php

namespace Tests\Feature\Todo;

use App\Models\DigitalAsset\SoftwareAccount;
use App\Models\DigitalAsset\SoftwareAccountReview;
use App\Models\DigitalAsset\SoftwarePlatform;
use App\Models\TodoList;
use App\Models\TodoReference;
use App\Models\TodoStatus;
use App\Models\User;
use App\Services\Todo\TodoReferenceService;
use App\Services\Todo\TodoTaskService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TodoReferenceTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected SoftwareAccountReview $review;
    protected TodoReferenceService $refService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'admin', 'suspended' => false]);
        $platform = SoftwarePlatform::create([
            'code' => 'ref_plat_' . uniqid(),
            'name' => 'Ref Platform',
            'status' => 'active',
        ]);
        $account = SoftwareAccount::create([
            'software_platform_id' => $platform->id,
            'account_name' => 'Ref Account',
            'username' => 'ref@company.test',
            'properties' => ['two_factor_enabled' => true],
            'created_by' => $this->user->id,
        ]);
        $this->review = SoftwareAccountReview::create([
            'software_account_id' => $account->id,
            'review_type' => 'ad_hoc',
            'status' => 'in_progress',
            'scheduled_at' => now(),
            'started_at' => now(),
            'assigned_to' => $this->user->id,
        ]);
        $this->refService = app(TodoReferenceService::class);
    }

    public function test_can_create_referenced_todo_and_trace_reason(): void
    {
        $ref = $this->refService->createReferencedTask(
            referenceModel: $this->review,
            taskData: [
                'task' => 'Fix missing recovery email configuration',
                'assigned_user_id' => $this->user->id,
                'due_date' => now()->addDays(7)->toDateTimeString(),
            ],
            reason: 'Audit finding: Recovery email is not set up on master account.',
            module: 'digital_asset_review',
            actionType: 'remediation',
            creator: $this->user
        );

        $this->assertDatabaseHas('todo_references', [
            'id' => $ref->id,
            'reference_type' => SoftwareAccountReview::class,
            'reference_id' => $this->review->id,
            'module' => 'digital_asset_review',
            'action_type' => 'remediation',
            'reason' => 'Audit finding: Recovery email is not set up on master account.',
        ]);

        $this->assertNotNull($ref->todo_list_id);
        $this->assertEquals('Fix missing recovery email configuration', $ref->todo->task);
        $this->assertEquals($this->review->id, $ref->reference->id);
        $this->assertEquals($this->user->id, $ref->created_by_user->id);

        // Test HasTodoReferences trait methods on the review model
        $this->assertTrue($this->review->hasPendingTodos());
        $this->assertFalse($this->review->hasCompletedTodos());
        $this->assertCount(1, $this->review->todoReferences);
    }

    public function test_todo_closing_synchronizes_resolution_via_todo_list(): void
    {
        $ref = $this->refService->createReferencedTask(
            referenceModel: $this->review,
            taskData: [
                'task' => 'Mandatory 2FA Enrollment Follow-Up',
                'assigned_user_id' => $this->user->id,
            ],
            reason: 'Account is at risk without 2FA.',
            creator: $this->user
        );

        $todo = $ref->todo;
        $this->assertFalse($ref->fresh()->is_resolved);

        // Close the Todo
        $completedStatus = TodoStatus::where('status', 'like', '%complete%')->first() ?? TodoStatus::first();
        $todo->update([
            'todo_status_id' => $completedStatus->id,
            'closed_by_user_id' => $this->user->id,
            'closed_at' => now(),
        ]);

        $ref->refresh();
        $this->assertTrue($ref->is_resolved);
        $this->assertNotNull($ref->resolved_at);
        $this->assertEquals($this->user->id, $ref->closed_by_user->id);

        // HasTodoReferences trait now reflects completed
        $this->assertTrue($this->review->hasCompletedTodos());
    }

    public function test_completing_review_with_todo_creation_creates_linked_action(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('digital-assets.reviews.complete', $this->review->id), [
            'result' => 'needs_action',
            'notes' => 'Contractor lacks verified security token',
            'create_todo' => true,
            'todo_task' => 'Issue YubiKey to contractor lead',
            'todo_assigned_to' => $this->user->id,
            'todo_reason' => 'Contractor lacks verified security token',
        ]);

        $response->assertRedirect();
        $this->review->refresh();
        $this->assertEquals('completed', $this->review->status);
        $this->assertEquals('needs_action', $this->review->result);

        $this->assertDatabaseHas('todo_references', [
            'reference_type' => SoftwareAccountReview::class,
            'reference_id' => $this->review->id,
            'reason' => 'Contractor lacks verified security token',
        ]);

        $this->assertDatabaseHas('todo_lists', [
            'task' => 'Issue YubiKey to contractor lead',
        ]);
    }

    public function test_can_create_todo_via_dedicated_endpoint(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('digital-assets.reviews.todos.create', $this->review->id), [
            'task' => 'Audit emergency backup codes',
            'assigned_user_id' => $this->user->id,
            'reason' => 'Emergency codes need re-encryption',
            'due_date' => now()->addDays(5)->toDateTimeString(),
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('todo_references', [
            'reference_type' => SoftwareAccountReview::class,
            'reference_id' => $this->review->id,
            'reason' => 'Emergency codes need re-encryption',
        ]);

        $this->assertDatabaseHas('todo_lists', [
            'task' => 'Audit emergency backup codes',
        ]);
    }

    public function test_can_fetch_todo_options_for_global_modal(): void
    {
        $this->actingAs($this->user);

        $response = $this->getJson(route('todo.options'));

        $response->assertOk();
        $response->assertJsonStructure([
            'dueTimes',
            'branches',
            'departments',
            'categories',
            'users',
            'itAdminDepartments',
        ]);
    }

    public function test_global_todo_task_store_automatically_links_reference(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('todo.tasks.store'), [
            'task' => 'Global Form: Re-verify SAML SSO Configuration',
            'assignedUserId' => $this->user->id,
            'reference_type' => SoftwareAccountReview::class,
            'reference_id' => $this->review->id,
            'module' => 'compliance_review',
            'reason' => 'SAML certificate expires in 7 days',
            'action_type' => 'remediation',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('todo_lists', [
            'task' => 'Global Form: Re-verify SAML SSO Configuration',
        ]);

        $this->assertDatabaseHas('todo_references', [
            'reference_type' => SoftwareAccountReview::class,
            'reference_id' => $this->review->id,
            'module' => 'compliance_review',
            'reason' => 'SAML certificate expires in 7 days',
            'action_type' => 'remediation',
        ]);
    }

    public function test_reviews_index_can_filter_by_todo_status(): void
    {
        $this->actingAs($this->user);

        // Initially review has no todos
        $responseNoTodo = $this->get(route('digital-assets.reviews.index', ['todo_status' => 'needs_todo']));
        $responseNoTodo->assertOk();

        // Now link a pending todo
        $ref = $this->refService->createReferencedTask(
            referenceModel: $this->review,
            taskData: [
                'task' => 'Follow up on review action',
                'assigned_user_id' => $this->user->id,
            ],
            creator: $this->user
        );

        $responsePending = $this->get(route('digital-assets.reviews.index', ['todo_status' => 'pending']));
        $responsePending->assertOk();
        $responsePending->assertInertia(fn($page) => $page
            ->component('DigitalAsset/Reviews/Index')
            ->has('reviews.data', 1)
            ->where('filters.todo_status', 'pending')
        );

        // Filter by completed (this pending review should not be in the completed list)
        $responseCompleted = $this->get(route('digital-assets.reviews.index', ['todo_status' => 'completed']));
        $responseCompleted->assertOk();
        $responseCompleted->assertInertia(fn($page) => $page
            ->where('filters.todo_status', 'completed')
            ->where('reviews.data', fn($items) => !collect($items)->pluck('id')->contains($this->review->id))
        );
    }

    public function test_reviews_index_surfaces_unaligned_accounts_in_live_radar_tab(): void
    {
        $this->actingAs($this->user);

        // Account created with missing mandatory 2FA property and 0 PICs
        $gapAccount = SoftwareAccount::create([
            'software_platform_id' => $this->review->account->software_platform_id,
            'account_name' => 'Unreviewed Risky Account',
            'username' => 'risky_user',
            'properties' => ['random_prop' => 'val'], // Missing required two_factor_enabled
            'lifecycle_status' => 'active',
            'security_status' => 'needs_review',
        ]);

        $response = $this->get(route('digital-assets.reviews.index'));
        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('DigitalAsset/Reviews/Index')
            ->has('unalignedAccounts')
            ->where('unalignedAccounts', fn($accounts) => collect($accounts)->pluck('id')->contains($gapAccount->id))
        );

        // Now trigger/link a remediation ToDo for this unaligned account
        $ref = $this->refService->createReferencedTask(
            referenceModel: $gapAccount,
            taskData: [
                'task' => 'Fix missing 2FA and assign PIC for Unreviewed Risky Account',
                'assigned_user_id' => $this->user->id,
            ],
            reason: 'Missing mandatory 2FA configuration and no active PIC',
            module: 'compliance_gap',
            creator: $this->user
        );

        $this->assertDatabaseHas('todo_references', [
            'reference_type' => SoftwareAccount::class,
            'reference_id' => $gapAccount->id,
            'module' => 'compliance_gap',
        ]);

        $responseWithTodo = $this->get(route('digital-assets.reviews.index'));
        $responseWithTodo->assertOk();
        $responseWithTodo->assertInertia(fn($page) => $page
            ->has('unalignedAccounts')
            ->where('unalignedAccounts', function ($accounts) use ($gapAccount) {
                $acc = collect($accounts)->firstWhere('id', $gapAccount->id);
                return $acc !== null && ($acc['has_pending_todo'] ?? false) === true;
            })
        );
    }
}
