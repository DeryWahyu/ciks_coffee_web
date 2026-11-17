<?php

namespace Tests\Feature;

use App\Models\CoffeeTable;
use App\Models\FloorLayout;
use App\Services\SystemExportData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTableAvailabilityFixtures;
use Tests\TestCase;

class TableChairPositionsTest extends TestCase
{
    use CreatesTableAvailabilityFixtures, RefreshDatabase;

    private function details(CoffeeTable $table): array
    {
        return $table->fresh()->only(['code', 'name', 'capacity', 'shape', 'position_x', 'position_y', 'width', 'height', 'rotation', 'is_active', 'version']);
    }

    public function test_owner_and_employee_can_move_each_chair_without_moving_or_changing_table_status(): void
    {
        $layout = FloorLayout::sole();
        foreach (['pemilik', 'karyawan'] as $role) {
            $table = $this->createCoffeeTable($layout, ['rotation' => 90, 'status' => 'occupied']);
            $original = $this->details($table);
            $chairs = ['left', 'right', 'front', 'back'];
            $this->actingAs($this->createTableUser($role), 'web')->get('/'.$role.'/meja')->assertOk()->assertSee("id='table-chair-mode'", false);
            $this->putJson('/'.$role.'/meja/'.$table->id, [...$original, 'chair_positions' => $chairs])
                ->assertOk()->assertJsonPath('data.chair_positions', $chairs)->assertJsonPath('data.version', 2);
            $current = $this->details($table);
            unset($original['version'], $current['version']);
            $this->assertSame($original, $current);
            $this->assertSame('occupied', $table->fresh()->status);
            $this->putJson('/'.$role.'/meja/'.$table->id, [...$original, 'version' => 1, 'chair_positions' => array_reverse($chairs)])->assertConflict();
            $this->getJson('/'.$role.'/meja/data')->assertOk()->assertJsonFragment(['chair_positions' => $chairs]);
            Sanctum::actingAs($this->createTableUser());
            $customer = $this->getJson('/api/table-layout')->assertOk();
            $this->assertSame($chairs, collect($customer->json('data.tables'))->keyBy('id')[$table->id]['chair_positions']);
        }
        $this->assertDatabaseCount('table_status_histories', 0);
    }

    public function test_invalid_chair_counts_sides_and_free_coordinates_are_rejected(): void
    {
        $table = $this->createCoffeeTable(FloorLayout::sole());
        $this->actingAs($this->createTableUser('karyawan'));
        foreach ([['left'], ['left', 'right', 'front', 'outside'], ['left', 'right', 'front', ['x' => 999, 'y' => 999]], ['first' => 'left', 'second' => 'right', 'third' => 'front', 'fourth' => 'back'], array_fill(0, 21, 'left')] as $positions) {
            $this->putJson('/karyawan/meja/'.$table->id, [...$this->details($table), 'chair_positions' => $positions])->assertUnprocessable();
        }
        $this->assertNull($table->fresh()->chair_positions);
        $this->assertSame(1, $table->fresh()->version);
    }

    public function test_custom_positions_can_be_created_exported_preserved_and_reset_to_automatic(): void
    {
        $layout = FloorLayout::sole();
        $owner = $this->createTableUser('pemilik');
        $chairs = ['left', 'left', 'right', 'front'];
        $this->actingAs($owner);
        $id = $this->postJson('/pemilik/meja', ['floor_layout_id' => $layout->id, 'code' => 'KURSI', 'name' => 'Meja kursi', 'capacity' => 4,
            'shape' => 'rectangle', 'position_x' => 20, 'position_y' => 25, 'width' => 10, 'height' => 8, 'rotation' => 0, 'is_active' => true, 'chair_positions' => $chairs])
            ->assertCreated()->assertJsonPath('data.chair_positions', $chairs)->json('data.id');
        $table = CoffeeTable::findOrFail($id);
        $this->putJson('/pemilik/meja/'.$id, [...$this->details($table), 'name' => 'Nama diperbarui'])->assertOk()->assertJsonPath('data.chair_positions', $chairs);
        $section = app(SystemExportData::class)->sections([])['meja'];
        $rows = ($section['rows'])()->all();
        $this->assertContains('Posisi Kursi JSON', $section['headers']);
        $this->assertSame($chairs, json_decode(end($rows)[17], true));
        $this->putJson('/pemilik/meja/'.$id, [...$this->details($table), 'capacity' => 2])->assertOk()->assertJsonPath('data.chair_positions', null);
        $this->putJson('/pemilik/meja/'.$id, [...$this->details($table), 'chair_positions' => ['left', 'right']])->assertOk();
        $this->putJson('/pemilik/meja/'.$id, [...$this->details($table), 'chair_positions' => null])->assertOk()->assertJsonPath('data.chair_positions', null);
    }
}
