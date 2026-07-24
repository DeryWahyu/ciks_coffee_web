<?php

namespace Tests\Feature;

use App\Models\CoffeeTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTableAvailabilityFixtures;
use Tests\TestCase;

class TableAvailabilityFeatureTest extends TestCase
{
    use CreatesTableAvailabilityFixtures;
    use RefreshDatabase;

    public function test_mobile_api_requires_authentication_and_hides_internal_table_fields(): void
    {
        $layout = $this->createFloorLayout();
        $employee = $this->createTableUser(User::ROLE_KARYAWAN);
        $activeTable = $this->createCoffeeTable($layout, [
            'code' => 'M01',
            'status' => CoffeeTable::STATUS_AVAILABLE,
            'status_note' => 'Catatan internal yang tidak boleh terkirim.',
            'status_updated_by' => $employee->id,
        ]);
        $this->createCoffeeTable($layout, [
            'code' => 'M02',
            'status' => CoffeeTable::STATUS_OCCUPIED,
            'is_active' => false,
        ]);

        $this->getJson('/api/table-layout')->assertUnauthorized();

        $customer = $this->createTableUser();
        Sanctum::actingAs($customer);

        $response = $this->getJson('/api/table-layout')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.summary.total', 1)
            ->assertJsonPath('data.summary.available', 1)
            ->assertJsonPath('data.summary.occupied', 0)
            ->assertJsonPath('data.tables.0.id', $activeTable->id)
            ->assertJsonPath('data.tables.0.status', CoffeeTable::STATUS_AVAILABLE)
            ->assertJsonCount(1, 'data.tables');

        $tablePayload = $response->json('data.tables.0');
        $this->assertIsArray($tablePayload);
        $this->assertArrayNotHasKey('status_note', $tablePayload);
        $this->assertArrayNotHasKey('status_updated_by', $tablePayload);
        $this->assertArrayNotHasKey('is_active', $tablePayload);
    }

    public function test_employee_status_endpoint_returns_conflict_for_a_stale_version(): void
    {
        $layout = $this->createFloorLayout();
        $table = $this->createCoffeeTable($layout);
        $employee = $this->createTableUser(User::ROLE_KARYAWAN);

        $this->actingAs($employee)
            ->patchJson('/karyawan/meja/' . $table->id . '/status', [
                'status' => CoffeeTable::STATUS_OCCUPIED,
                'note' => 'Pelanggan datang.',
                'version' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', CoffeeTable::STATUS_OCCUPIED)
            ->assertJsonPath('data.version', 2);

        $this->actingAs($employee)
            ->patchJson('/karyawan/meja/' . $table->id . '/status', [
                'status' => CoffeeTable::STATUS_RESERVED,
                'version' => 1,
            ])
            ->assertConflict()
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.status', CoffeeTable::STATUS_OCCUPIED)
            ->assertJsonPath('data.version', 2);

        $this->assertDatabaseCount('table_status_histories', 1);
        $this->assertDatabaseHas('coffee_tables', [
            'id' => $table->id,
            'status' => CoffeeTable::STATUS_OCCUPIED,
            'version' => 2,
        ]);
    }

    public function test_owner_create_response_is_hydrated_with_database_defaults(): void
    {
        $layout = $this->createFloorLayout();
        $owner = $this->createTableUser(User::ROLE_PEMILIK);

        $this->actingAs($owner)
            ->postJson('/pemilik/meja', [
                'floor_layout_id' => $layout->id,
                'code' => 'M99',
                'name' => 'Meja Uji',
                'capacity' => 4,
                'shape' => CoffeeTable::SHAPE_SQUARE,
                'position_x' => 70,
                'position_y' => 70,
                'width' => 15,
                'height' => 15,
                'rotation' => 0,
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', CoffeeTable::STATUS_AVAILABLE)
            ->assertJsonPath('data.status_label', 'Tersedia')
            ->assertJsonPath('data.version', 1);

        $this->assertDatabaseHas('coffee_tables', [
            'floor_layout_id' => $layout->id,
            'code' => 'M99',
            'status' => CoffeeTable::STATUS_AVAILABLE,
            'version' => 1,
        ]);
    }

    public function test_employee_and_inactive_owner_are_blocked_from_owner_table_management(): void
    {
        $layout = $this->createFloorLayout();
        $table = $this->createCoffeeTable($layout);
        $employee = $this->createTableUser(User::ROLE_KARYAWAN);
        $inactiveOwner = $this->createTableUser(User::ROLE_PEMILIK, false);

        $this->actingAs($employee)
            ->putJson('/pemilik/meja/layout/' . $layout->id, [
                'tables' => [[
                    'id' => $table->id,
                    'position_x' => 10,
                    'position_y' => 10,
                    'width' => 15,
                    'height' => 15,
                    'rotation' => 0,
                    'version' => 1,
                ]],
            ])
            ->assertForbidden();

        $this->actingAs($inactiveOwner)
            ->get('/pemilik/meja')
            ->assertForbidden();
    }

    public function test_owner_table_geometry_outside_canvas_is_rejected(): void
    {
        $layout = $this->createFloorLayout();
        $owner = $this->createTableUser(User::ROLE_PEMILIK);

        $this->actingAs($owner)
            ->postJson('/pemilik/meja', [
                'floor_layout_id' => $layout->id,
                'code' => 'M98',
                'name' => 'Meja Keluar Kanvas',
                'capacity' => 2,
                'shape' => CoffeeTable::SHAPE_ROUND,
                'position_x' => 94,
                'position_y' => 10,
                'width' => 10,
                'height' => 10,
                'is_active' => true,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('width');

        $this->assertDatabaseMissing('coffee_tables', ['code' => 'M98']);
    }
}
