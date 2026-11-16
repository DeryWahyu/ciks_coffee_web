<?php

namespace Tests\Feature;

use App\Models\CoffeeTable;
use App\Models\FloorLayout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTableAvailabilityFixtures;
use Tests\TestCase;

class EmployeeTableEditorTest extends TestCase
{
    use CreatesTableAvailabilityFixtures;
    use RefreshDatabase;

    public function test_employee_uses_the_shared_editor_with_own_routes_and_dot_status_indicators(): void
    {
        $this->actingAs($this->createTableUser(User::ROLE_KARYAWAN))
            ->get('/karyawan/meja')->assertOk()
            ->assertSee('Ketersediaan Meja')
            ->assertSee("id='owner-edit-toggle'", false)
            ->assertSee("id='owner-add-table'", false)
            ->assertSee('status-dots-only', false)
            ->assertDontSee('owner-layout-description', false)
            ->assertSee(json_encode(route('karyawan.tables.layout.update', ['floorLayout' => '__LAYOUT_ID__'])), false)
            ->assertDontSee("id='owner-history'", false);

        $this->actingAs($this->createTableUser(User::ROLE_PEMILIK))
            ->get('/pemilik/meja')->assertOk()
            ->assertSee('Manajemen Meja')
            ->assertSee("id='owner-history'", false);
    }

    public function test_employee_can_create_edit_and_delete_an_unused_table(): void
    {
        $layout = FloorLayout::query()->sole();
        $this->actingAs($this->createTableUser(User::ROLE_KARYAWAN));
        $attributes = $this->attributes($layout);
        $response = $this->postJson('/karyawan/meja', $attributes)->assertCreated()
            ->assertJsonPath('data.code', 'K01')->assertJsonPath('data.version', 1);
        $id = $response->json('data.id');
        $this->putJson('/karyawan/meja/'.$id, [...$attributes, 'name' => 'Meja diperbarui', 'version' => 1])
            ->assertOk()->assertJsonPath('data.name', 'Meja diperbarui')->assertJsonPath('data.version', 2);
        $this->deleteJson('/karyawan/meja/'.$id, ['version' => 1])->assertConflict();
        $this->deleteJson('/karyawan/meja/'.$id, ['version' => 2])->assertOk();
        $this->assertDatabaseMissing('coffee_tables', ['id' => $id]);
    }

    public function test_employee_layout_changes_are_visible_to_owner_and_customer_without_changing_status(): void
    {
        $layout = FloorLayout::query()->sole();
        $table = $this->createCoffeeTable($layout, ['status' => CoffeeTable::STATUS_OCCUPIED]);
        $position = [...$table->only(['id', 'position_x', 'position_y', 'width', 'height', 'rotation', 'version']), 'position_x' => 30];
        $this->actingAs($this->createTableUser(User::ROLE_KARYAWAN))
            ->putJson('/karyawan/meja/layout/'.$layout->id, [
                'tables' => [$position], 'background_config' => ['elements' => [
                    ['type' => 'text', 'label' => 'Indoor', 'position_x' => 50, 'position_y' => 50],
                ]],
            ])->assertOk()->assertJsonPath('data.tables.0.position_x', 30)
            ->assertJsonPath('data.tables.0.status', CoffeeTable::STATUS_OCCUPIED);
        $this->assertDatabaseCount('table_status_histories', 0);

        $this->actingAs($this->createTableUser(User::ROLE_PEMILIK))
            ->getJson('/pemilik/meja/data')->assertOk()->assertJsonPath('data.tables.0.position_x', 30)
            ->assertJsonPath('data.layout.background_config.elements.0.label', 'Indoor');
        Sanctum::actingAs($this->createTableUser());
        $this->getJson('/api/table-layout')->assertOk()->assertJsonPath('data.tables.0.position_x', 30)
            ->assertJsonMissingPath('data.tables.0.status_note');
    }

    public function test_stale_employee_layout_save_cannot_overwrite_a_newer_owner_update(): void
    {
        $layout = FloorLayout::query()->sole();
        $table = $this->createCoffeeTable($layout);
        $position = $table->only(['id', 'position_x', 'position_y', 'width', 'height', 'rotation', 'version']);
        $this->actingAs($this->createTableUser(User::ROLE_PEMILIK))
            ->putJson('/pemilik/meja/layout/'.$layout->id, ['tables' => [[...$position, 'position_x' => 20]]])
            ->assertOk();
        $this->actingAs($this->createTableUser(User::ROLE_KARYAWAN))
            ->putJson('/karyawan/meja/layout/'.$layout->id, [
                'name' => 'Jangan disimpan', 'tables' => [[...$position, 'position_x' => 40]],
            ])->assertConflict();
        $this->assertSame(20.0, (float) $table->fresh()->position_x);
        $this->assertNotSame('Jangan disimpan', $layout->fresh()->name);
    }

    public function test_employee_must_archive_tables_with_status_history_and_customer_cannot_see_archived_tables(): void
    {
        $layout = FloorLayout::query()->sole();
        $table = $this->createCoffeeTable($layout);
        $employee = $this->createTableUser(User::ROLE_KARYAWAN);
        $this->actingAs($employee)->patchJson('/karyawan/meja/'.$table->id.'/status', [
            'status' => CoffeeTable::STATUS_OCCUPIED, 'version' => 1,
        ])->assertOk();
        $this->assertDatabaseHas('table_status_histories', ['coffee_table_id' => $table->id, 'user_id' => $employee->id, 'source' => 'employee_web']);
        $this->deleteJson('/karyawan/meja/'.$table->id, ['version' => 2])->assertUnprocessable();
        $this->patchJson('/karyawan/meja/'.$table->id.'/toggle-active', ['is_active' => false, 'version' => 2])
            ->assertOk()->assertJsonPath('data.is_active', false);
        $this->getJson('/karyawan/meja/data')->assertOk()->assertJsonCount(1, 'data.tables')
            ->assertJsonPath('data.tables.0.is_active', false);
        Sanctum::actingAs($this->createTableUser());
        $this->getJson('/api/table-layout')->assertOk()->assertJsonCount(0, 'data.tables');
    }

    public function test_customers_inactive_staff_and_anonymous_visitors_cannot_use_editor_endpoints(): void
    {
        $layout = FloorLayout::query()->sole();
        $this->postJson('/karyawan/meja', $this->attributes($layout))->assertUnauthorized();
        foreach ([$this->createTableUser(), $this->createTableUser(User::ROLE_KARYAWAN, false)] as $user) {
            $this->actingAs($user)->postJson('/karyawan/meja', $this->attributes($layout))->assertForbidden();
            $this->putJson('/karyawan/meja/layout/'.$layout->id, ['tables' => []])->assertForbidden();
        }
        $this->assertDatabaseCount('coffee_tables', 0);
    }

    public function test_employee_cannot_open_owner_history_and_invalid_layout_geometry_is_rejected(): void
    {
        $layout = FloorLayout::query()->sole();
        $table = $this->createCoffeeTable($layout);
        $position = $table->only(['id', 'position_x', 'position_y', 'width', 'height', 'rotation', 'version']);
        $this->actingAs($this->createTableUser(User::ROLE_KARYAWAN))
            ->getJson('/pemilik/meja/riwayat')->assertForbidden();
        $this->putJson('/karyawan/meja/layout/'.$layout->id, ['tables' => [[...$position, 'position_x' => 99]]])
            ->assertUnprocessable()->assertJsonValidationErrors('tables.0.width');
        $this->assertSame(10.0, (float) $table->fresh()->position_x);
    }

    private function attributes(FloorLayout $layout): array
    {
        return ['floor_layout_id' => $layout->id, 'code' => 'K01', 'name' => 'Meja karyawan', 'capacity' => 4,
            'shape' => CoffeeTable::SHAPE_SQUARE, 'position_x' => 10, 'position_y' => 10, 'width' => 10, 'height' => 10, 'is_active' => true,
        ];
    }
}
