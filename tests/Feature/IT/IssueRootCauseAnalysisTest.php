<?php

namespace Tests\Feature\IT;

use App\IssueTracking\Models\Issue;
use App\IssueTracking\Models\IssueCategory;
use App\IssueTracking\Models\IssueImportanceLevel;
use App\IssueTracking\Models\IssuePriority;
use App\IssueTracking\Models\IssueRootCause;
use App\IssueTracking\Models\IssueStatus;
use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class IssueRootCauseAnalysisTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected IssueCategory $category;
    protected IssuePriority $priority;
    protected IssueImportanceLevel $importance;
    protected IssueStatus $status;
    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $position = Position::firstOrCreate(['name' => 'IT Admin ' . uniqid()]);
        $branch = \App\Models\Branch::firstOrCreate(['name' => 'Main Branch ' . uniqid()]);
        $this->department = Department::firstOrCreate(['name' => 'IT ' . uniqid()]);
        $loc = \App\Models\Location::firstOrCreate(['name' => 'Main Location ' . uniqid()]);

        $this->user = User::create([
            'name' => 'Test IT User',
            'email' => 'it.' . uniqid() . '@example.com',
            'password' => bcrypt('secret'),
            'position_id' => $position->id,
            'branch_id' => $branch->id,
            'department_id' => $this->department->id,
            'location_id' => $loc->id,
        ]);

        $this->category = IssueCategory::firstOrCreate(['name' => 'ERP Backend', 'is_erp' => true]);
        $this->priority = IssuePriority::firstOrCreate(['name' => 'High', 'level' => 1]);
        $this->importance = IssueImportanceLevel::firstOrCreate(['name' => 'Critical', 'level' => 1]);
        $this->status = IssueStatus::firstOrCreate(['code' => 'OPEN'], ['name' => 'Open']);

        $this->actingAs($this->user);
    }

    private function createSampleIssue(string $title = 'Sample Issue'): Issue
    {
        return Issue::create([
            'title' => $title,
            'description' => 'Detailed description of ' . $title,
            'issue_category_id' => $this->category->id,
            'issue_priority_id' => $this->priority->id,
            'issue_importance_id' => $this->importance->id,
            'issue_status_id' => $this->status->id,
            'resolution_department_id' => $this->department->id,
            'created_by' => $this->user->id,
            'issue_at' => now(),
        ]);
    }

    public function test_can_view_root_causes_page(): void
    {
        $response = $this->get(route('operation.it.issues.root-causes.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Operation/IT/Issues/RootCauses')
            ->has('issues')
            ->has('summary')
            ->has('rootCauses')
        );
    }

    public function test_issues_are_paginated_five_items_per_page(): void
    {
        // Create 6 issues to exceed single page of 5
        for ($i = 1; $i <= 6; $i++) {
            $this->createSampleIssue("Test Issue {$i} " . uniqid());
        }

        $response = $this->get(route('operation.it.issues.root-causes.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Operation/IT/Issues/RootCauses')
            ->where('issues.per_page', 5)
        );
    }

    public function test_can_store_new_root_cause_type_with_category(): void
    {
        $name = 'Database Deadlock ' . uniqid();
        $category = 'Software';

        $response = $this->post(route('operation.it.issues.root-causes.store'), [
            'name' => $name,
            'category' => $category,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('issue_root_causes', [
            'name' => $name,
            'category' => $category,
        ]);
    }

    public function test_can_update_issue_root_cause_and_remark(): void
    {
        $issue = $this->createSampleIssue();
        $rootCause = IssueRootCause::create([
            'name' => 'Memory Leak ' . uniqid(),
            'category' => 'Software',
        ]);
        $remarkText = 'Identified memory leak in worker queue daemon.';

        $response = $this->post(route('operation.it.issues.root-cause.update', $issue->id), [
            'root_cause_id' => $rootCause->id,
            'remark' => $remarkText,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('issue_messages', [
            'issue_id' => $issue->id,
            'is_log_note' => true,
        ]);
        if (\Illuminate\Support\Facades\Schema::hasColumn('issue_root_cause_logs', 'remark')) {
            $this->assertDatabaseHas('issue_root_cause_logs', [
                'issue_id' => $issue->id,
                'root_cause_id' => $rootCause->id,
                'remark' => $remarkText,
            ]);
        }
    }

    public function test_filter_by_root_cause_created_date(): void
    {
        $issue1 = $this->createSampleIssue('Issue Logged Today');
        $issue2 = $this->createSampleIssue('Issue Logged Yesterday');

        $rootCause = IssueRootCause::create([
            'name' => 'Timeout ' . uniqid(),
            'category' => 'Network',
        ]);

        $todayStr = now()->format('Y-m-d');
        $yesterdayStr = now()->subDays(1)->format('Y-m-d');

        // Associate issue1 with today
        $issue1->rootCauses()->attach($rootCause->id, [
            'remark' => 'Logged today',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Associate issue2 with yesterday
        $issue2->rootCauses()->attach($rootCause->id, [
            'remark' => 'Logged yesterday',
            'created_at' => now()->subDays(1),
            'updated_at' => now()->subDays(1),
        ]);

        // Query with filter for today
        $response = $this->get(route('operation.it.issues.root-causes.index', ['root_cause_date' => $todayStr]));
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Operation/IT/Issues/RootCauses')
            ->has('issues.data', fn (Assert $issues) => $issues
                ->where('0.id', $issue1->id)
                ->etc()
            )
        );
    }

    public function test_summary_groups_by_root_cause_category_and_issue_category(): void
    {
        $issue = $this->createSampleIssue('Issue for Summary');
        $rootCause = IssueRootCause::create([
            'name' => 'Hardware Burnout ' . uniqid(),
            'category' => 'Hardware',
        ]);

        $issue->rootCauses()->attach($rootCause->id, [
            'remark' => 'Power supply fried',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get(route('operation.it.issues.root-causes.index'));
        $response->assertStatus(200);

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Operation/IT/Issues/RootCauses')
            ->where('summary', function ($summary) use ($rootCause) {
                $hardwareCat = collect($summary)->firstWhere('root_cause_category', 'Hardware');
                if (!$hardwareCat) return false;
                $issueCat = collect($hardwareCat['issue_categories'])->firstWhere('issue_category_name', $this->category->name);
                if (!$issueCat) return false;
                return collect($issueCat['root_causes'])->contains('name', $rootCause->name);
            })
        );
    }
}
