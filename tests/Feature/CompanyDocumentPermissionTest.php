<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CompanyDocument;
use App\Models\CompanyDocumentType;
use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CompanyDocumentPermissionTest extends TestCase
{
    use DatabaseTransactions;

    protected User $regularUser;
    protected User $superAdmin;
    protected Department $department;
    protected CompanyDocumentType $documentType;
    protected CompanyDocument $document;

    protected function setUp(): void
    {
        parent::setUp();

        config(['scout.driver' => null]);

        // Ensure permissions exist
        Permission::firstOrCreate(['name' => 'document.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'document.update', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'document.delete', 'guard_name' => 'web']);

        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $branch = Branch::firstOrCreate(['name' => 'HQ Branch']);
        $position = Position::firstOrCreate(['name' => 'Staff']);
        $superPos = Position::firstOrCreate(['name' => 'Super Admin']);

        $this->department = Department::firstOrCreate(['name' => 'Operations']);
        $this->documentType = CompanyDocumentType::firstOrCreate(['name' => 'SOP']);

        $this->regularUser = User::create([
            'name' => 'Regular User',
            'email' => 'regular.' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'position_id' => $position->id,
            'branch_id' => $branch->id,
            'department_id' => $this->department->id,
        ]);

        $this->superAdmin = User::create([
            'name' => 'Super Admin User',
            'email' => 'superadmin.' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'position_id' => $superPos->id,
            'branch_id' => $branch->id,
            'department_id' => $this->department->id,
        ]);
        $this->superAdmin->assignRole($superAdminRole);

        $this->document = CompanyDocument::create([
            'title' => 'Standard Safety Procedures',
            'department_id' => $this->department->id,
            'company_document_type_id' => $this->documentType->id,
            'body' => '<p>Safety procedures content</p>',
            'content_text' => 'Safety procedures content',
            'created_by' => $this->superAdmin->id,
            'updated_by' => $this->superAdmin->id,
        ]);
    }

    public function test_all_users_can_view_document_library_by_default(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('document.library.index'));
        $response->assertStatus(200);

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Document/Library/Index')
            ->where('can.create', false)
            ->where('can.update', false)
            ->where('can.delete', false)
            ->where('permissions.can_create', false)
            ->where('permissions.can_update', false)
            ->where('permissions.can_delete', false)
        );

        // Show route redirects to index with doc param
        $showResponse = $this->actingAs($this->regularUser)->get(route('document.library.show', $this->document));
        $showResponse->assertRedirect(route('document.library.index', ['doc' => $this->document->id]));
    }

    public function test_user_without_document_create_permission_cannot_access_create_or_store(): void
    {
        $createResponse = $this->actingAs($this->regularUser)->get(route('document.library.create'));
        $createResponse->assertStatus(403);

        $storeResponse = $this->actingAs($this->regularUser)->post(route('document.library.store'), [
            'title' => 'Unauthorized Document',
            'department_id' => $this->department->id,
            'company_document_type_id' => $this->documentType->id,
            'body' => '<p>Content</p>',
        ]);
        $storeResponse->assertStatus(403);
    }

    public function test_user_with_document_create_permission_can_access_create_and_store(): void
    {
        $this->regularUser->givePermissionTo('document.create');

        $createResponse = $this->actingAs($this->regularUser)->get(route('document.library.create'));
        $createResponse->assertStatus(200);

        $storeResponse = $this->actingAs($this->regularUser)->post(route('document.library.store'), [
            'title' => 'Authorized New Document',
            'department_id' => $this->department->id,
            'company_document_type_id' => $this->documentType->id,
            'body' => '<p>New document body content</p>',
        ]);
        $storeResponse->assertRedirect();

        $this->assertDatabaseHas('company_documents', [
            'title' => 'Authorized New Document',
            'created_by' => $this->regularUser->id,
        ]);
    }

    public function test_user_without_document_update_permission_cannot_access_edit_or_update(): void
    {
        $editResponse = $this->actingAs($this->regularUser)->get(route('document.library.edit', $this->document));
        $editResponse->assertStatus(403);

        $updateResponse = $this->actingAs($this->regularUser)->put(route('document.library.update', $this->document), [
            'title' => 'Unauthorized Update',
            'department_id' => $this->department->id,
            'company_document_type_id' => $this->documentType->id,
            'body' => '<p>Updated content</p>',
        ]);
        $updateResponse->assertStatus(403);
    }

    public function test_user_with_document_update_permission_can_access_edit_and_update(): void
    {
        $this->regularUser->givePermissionTo('document.update');

        $editResponse = $this->actingAs($this->regularUser)->get(route('document.library.edit', $this->document));
        $editResponse->assertStatus(200);

        $updateResponse = $this->actingAs($this->regularUser)->put(route('document.library.update', $this->document), [
            'title' => 'Authorized Updated Title',
            'department_id' => $this->department->id,
            'company_document_type_id' => $this->documentType->id,
            'body' => '<p>Authorized updated content</p>',
        ]);
        $updateResponse->assertRedirect();

        $this->assertDatabaseHas('company_documents', [
            'id' => $this->document->id,
            'title' => 'Authorized Updated Title',
            'updated_by' => $this->regularUser->id,
        ]);
    }

    public function test_regular_user_cannot_delete_company_document(): void
    {
        $deleteResponse = $this->actingAs($this->regularUser)->delete(route('document.library.destroy', $this->document));
        $deleteResponse->assertStatus(403);

        $this->assertDatabaseHas('company_documents', [
            'id' => $this->document->id,
        ]);
    }

    public function test_super_admin_can_delete_company_document(): void
    {
        $deleteResponse = $this->actingAs($this->superAdmin)->delete(route('document.library.destroy', $this->document));
        $deleteResponse->assertRedirect(route('document.library.index'));

        $this->assertSoftDeleted('company_documents', [
            'id' => $this->document->id,
        ]);
    }

    public function test_super_admin_has_all_document_permissions_in_view(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('document.library.index'));
        $response->assertStatus(200);

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Document/Library/Index')
            ->where('can.create', true)
            ->where('can.update', true)
            ->where('can.delete', true)
            ->where('permissions.can_create', true)
            ->where('permissions.can_update', true)
            ->where('permissions.can_delete', true)
        );
    }
}
