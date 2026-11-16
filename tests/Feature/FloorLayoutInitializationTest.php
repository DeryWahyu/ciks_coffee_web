<?php

namespace Tests\Feature;

use App\Models\CoffeeTable;
use App\Models\FloorLayout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTableAvailabilityFixtures;
use Tests\TestCase;

class FloorLayoutInitializationTest extends TestCase
{
    use CreatesTableAvailabilityFixtures;
    use RefreshDatabase;

    public function test_fresh_database_has_an_empty_layout_available_to_owner_employee_and_mobile(): void
    {
        $this->assertDatabaseCount('floor_layouts', 1);
        $this->assertDatabaseCount('coffee_tables', 0);

        $owner = $this->createTableUser(User::ROLE_PEMILIK);
        $this->actingAs($owner)->getJson('/pemilik/meja/data')
            ->assertOk()
            ->assertJsonPath('data.layout.name', 'Area Utama')
            ->assertJsonPath('data.layout.background_config.elements', [])
            ->assertJsonPath('data.summary.total', 0);

        $employee = $this->createTableUser(User::ROLE_KARYAWAN);
        $this->actingAs($employee)->getJson('/karyawan/meja/data')
            ->assertOk()
            ->assertJsonCount(0, 'data.tables');

        Sanctum::actingAs($this->createTableUser());
        $this->getJson('/api/table-layout')->assertOk()->assertJsonCount(0, 'data.tables');
    }

    public function test_upgrade_initializes_a_database_without_layouts_and_can_be_repeated(): void
    {
        FloorLayout::query()->delete();
        $migration = require database_path('migrations/2026_11_03_000001_initialize_empty_floor_layout.php');

        $migration->up();
        $layout = FloorLayout::query()->sole();
        $migration->up();

        $this->assertDatabaseCount('floor_layouts', 1);
        $this->assertDatabaseCount('coffee_tables', 0);
        $this->assertSame($layout->id, FloorLayout::query()->sole()->id);
        $this->assertTrue($layout->is_active);
    }

    public function test_upgrade_preserves_existing_inactive_layout_and_occupied_table(): void
    {
        FloorLayout::query()->delete();
        $layout = $this->createFloorLayout(['is_active' => false]);
        $table = $this->createCoffeeTable($layout, ['status' => CoffeeTable::STATUS_OCCUPIED]);
        $beforeLayout = $layout->fresh()->getAttributes();
        $beforeTable = $table->fresh()->getAttributes();

        $migration = require database_path('migrations/2026_11_03_000001_initialize_empty_floor_layout.php');
        $migration->up();

        $this->assertDatabaseCount('floor_layouts', 1);
        $this->assertSame($beforeLayout, $layout->fresh()->getAttributes());
        $this->assertSame($beforeTable, $table->fresh()->getAttributes());
    }

    public function test_rollback_preserves_initial_layout_after_owner_customization(): void
    {
        $layout = FloorLayout::query()->sole();
        $layout->update(['name' => 'Area Kedai', 'background_config' => ['show_grid' => false, 'elements' => []]]);
        $table = $this->createCoffeeTable($layout);

        $migration = require database_path('migrations/2026_11_03_000001_initialize_empty_floor_layout.php');
        $migration->down();

        $this->assertDatabaseHas('floor_layouts', ['id' => $layout->id, 'name' => 'Area Kedai']);
        $this->assertDatabaseHas('coffee_tables', ['id' => $table->id, 'floor_layout_id' => $layout->id]);
    }
}
