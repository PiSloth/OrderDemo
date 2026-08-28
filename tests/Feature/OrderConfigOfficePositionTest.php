<?php

namespace Tests\Feature;

use App\Livewire\Orders\Config;
use App\Models\OfficePosition;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class OrderConfigOfficePositionTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $position = Position::firstOrCreate(['name' => 'IT Admin ' . uniqid()]);
        $branch = \App\Models\Branch::firstOrCreate(['name' => 'Main Branch ' . uniqid()]);
        $dept = \App\Models\Department::firstOrCreate(['name' => 'IT Department ' . uniqid()]);
        $loc = \App\Models\Location::firstOrCreate(['name' => 'Main Location ' . uniqid()]);

        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin.' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'position_id' => $position->id,
            'branch_id' => $branch->id,
            'department_id' => $dept->id,
            'location_id' => $loc->id,
        ]);

        $this->actingAs($user);
    }

    public function test_can_create_office_position(): void
    {
        $name = 'Senior Sales Specialist ' . uniqid();
        $desc = 'Handles VIP clients and high-value orders';

        Livewire::test(Config::class)
            ->set('office_position_name', $name)
            ->set('office_position_description', $desc)
            ->call('create_office_position')
            ->assertHasNoErrors()
            ->assertSee($name);

        $this->assertDatabaseHas('office_positions', [
            'name' => $name,
            'description' => $desc,
        ]);
    }

    public function test_can_update_office_position(): void
    {
        $op = OfficePosition::create([
            'name' => 'Inventory Assistant ' . uniqid(),
            'description' => 'Original description',
        ]);

        $updatedName = 'Lead Inventory Officer ' . uniqid();
        $updatedDesc = 'Manages warehouse staff and inventory';

        Livewire::test(Config::class)
            ->call('editOfficePosition', $op->id)
            ->assertSet('editingOfficePositionId', $op->id)
            ->assertSet('editingOfficePositionName', $op->name)
            ->set('editingOfficePositionName', $updatedName)
            ->set('editingOfficePositionDescription', $updatedDesc)
            ->call('updateOfficePosition')
            ->assertHasNoErrors()
            ->assertSet('editingOfficePositionId', null);

        $this->assertDatabaseHas('office_positions', [
            'id' => $op->id,
            'name' => $updatedName,
            'description' => $updatedDesc,
        ]);
    }

    public function test_can_delete_office_position(): void
    {
        $op = OfficePosition::create([
            'name' => 'Temporary Position ' . uniqid(),
            'description' => 'Temporary',
        ]);

        Livewire::test(Config::class)
            ->call('delete_office_position', $op->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('office_positions', [
            'id' => $op->id,
        ]);
    }
}
