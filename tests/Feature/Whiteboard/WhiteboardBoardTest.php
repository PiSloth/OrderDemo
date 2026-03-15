<?php

namespace Tests\Feature\Whiteboard;

use App\Models\Department;
use App\Models\EmailList;
use App\Models\User;
use App\Models\WhiteboardContent;
use App\Models\WhiteboardContentType;
use App\Models\WhiteboardFlag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhiteboardBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_board_only_shows_content_assigned_to_the_user_or_their_department(): void
    {
        $operations = Department::factory()->create(['name' => 'Operations']);
        $finance = Department::factory()->create(['name' => 'Finance']);

        $viewer = User::factory()->create([
            'department_id' => $operations->id,
            'email' => 'viewer@example.com',
        ]);

        $author = User::factory()->create([
            'department_id' => $finance->id,
            'email' => 'author@example.com',
        ]);

        $viewerEmailList = EmailList::query()->create([
            'user_name' => 'Viewer',
            'email' => $viewer->email,
            'department_id' => $operations->id,
        ]);

        $operationsAlias = EmailList::query()->create([
            'user_name' => 'Operations Team',
            'email' => 'operations@example.com',
            'department_id' => $operations->id,
        ]);

        $financeAlias = EmailList::query()->create([
            'user_name' => 'Finance Team',
            'email' => 'finance@example.com',
            'department_id' => $finance->id,
        ]);

        $type = WhiteboardContentType::query()->create([
            'name' => 'Issue',
            'color' => '#2563EB',
        ]);

        $flag = WhiteboardFlag::query()->create([
            'name' => 'Urgent',
            'color' => '#DC2626',
        ]);

        $directContent = WhiteboardContent::query()->create([
            'title' => 'Direct notice',
            'description' => 'Assigned directly to the viewer.',
            'report_by' => $financeAlias->id,
            'created_by' => $author->id,
            'content_type_id' => $type->id,
            'flag_id' => $flag->id,
        ]);
        $directContent->reports()->create(['email_list_id' => $viewerEmailList->id]);

        $departmentContent = WhiteboardContent::query()->create([
            'title' => 'Department notice',
            'description' => 'Assigned to the operations department.',
            'report_by' => $financeAlias->id,
            'created_by' => $author->id,
            'content_type_id' => $type->id,
            'flag_id' => $flag->id,
        ]);
        $departmentContent->reports()->create(['email_list_id' => $operationsAlias->id]);

        $hiddenContent = WhiteboardContent::query()->create([
            'title' => 'Finance only',
            'description' => 'The viewer should not see this one.',
            'report_by' => $financeAlias->id,
            'created_by' => $author->id,
            'content_type_id' => $type->id,
            'flag_id' => $flag->id,
        ]);
        $hiddenContent->reports()->create(['email_list_id' => $financeAlias->id]);

        $visibleIds = WhiteboardContent::query()
            ->visibleTo($viewer)
            ->pluck('id');

        $this->assertTrue($visibleIds->contains($directContent->id));
        $this->assertTrue($visibleIds->contains($departmentContent->id));
        $this->assertFalse($visibleIds->contains($hiddenContent->id));

        $departmentContent->markReadFor($viewer);

        $this->assertDatabaseHas('whiteboard_reports', [
            'content_id' => $departmentContent->id,
            'email_list_id' => $operationsAlias->id,
            'is_read' => true,
            'read_by_user_id' => $viewer->id,
        ]);

        $this->actingAs($viewer)
            ->get(route('whiteboard.board'))
            ->assertOk()
            ->assertSee('Direct notice')
            ->assertSee('Department notice')
            ->assertDontSee('Finance only');
    }
}
