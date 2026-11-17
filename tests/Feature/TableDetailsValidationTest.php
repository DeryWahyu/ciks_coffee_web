<?php

namespace Tests\Feature;

use App\Models\FloorLayout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTableAvailabilityFixtures;
use Tests\TestCase;

class TableDetailsValidationTest extends TestCase
{
    use CreatesTableAvailabilityFixtures, RefreshDatabase;

    private const DUPLICATE_MESSAGE = 'Kode meja sudah digunakan pada denah ini, termasuk meja yang diarsipkan. Gunakan kode lain atau ubah meja yang sudah ada.';

    private function details(string $code): array
    {
        return ['floor_layout_id' => FloorLayout::sole()->id, 'code' => $code, 'name' => 'Meja uji', 'capacity' => 2, 'shape' => 'square',
            'position_x' => 30, 'position_y' => 30, 'width' => 10, 'height' => 10, 'rotation' => 0, 'is_active' => true, 'chair_positions' => ['left', 'right']];
    }

    public function test_duplicate_active_and_archived_codes_return_clear_indonesian_errors_for_both_roles(): void
    {
        $layout = FloorLayout::sole();
        $this->createCoffeeTable($layout, ['code' => 'M01']);
        $this->createCoffeeTable($layout, ['code' => 'M02', 'is_active' => false]);
        foreach (['pemilik', 'karyawan'] as $role) {
            $this->actingAs($this->createTableUser($role), 'web');
            foreach (['M01', 'M02'] as $code) {
                $this->postJson('/'.$role.'/meja', $this->details($code))->assertUnprocessable()
                    ->assertJsonPath('errors.code.0', self::DUPLICATE_MESSAGE)->assertDontSee('validation.unique');
            }
        }
        $this->assertDatabaseCount('coffee_tables', 2);
    }

    public function test_codes_are_normalized_before_unique_validation_and_creation(): void
    {
        $layout = FloorLayout::sole();
        $this->createCoffeeTable($layout, ['code' => 'M01']);
        $this->actingAs($this->createTableUser('pemilik'), 'web');
        $this->postJson('/pemilik/meja', $this->details(' m01 '))->assertUnprocessable()->assertJsonPath('errors.code.0', self::DUPLICATE_MESSAGE);
        $this->postJson('/pemilik/meja', $this->details(' m03 '))->assertCreated()->assertJsonPath('data.code', 'M03');
        $this->assertDatabaseCount('coffee_tables', 2);
    }

    public function test_editing_own_code_succeeds_but_renaming_to_another_tables_code_does_not(): void
    {
        $layout = FloorLayout::sole();
        $this->createCoffeeTable($layout, ['code' => 'M01']);
        foreach (['pemilik', 'karyawan'] as $index => $role) {
            $table = $this->createCoffeeTable($layout, ['code' => 'T'.$index]);
            $this->actingAs($this->createTableUser($role), 'web');
            $this->putJson('/'.$role.'/meja/'.$table->id, [...$this->details('t'.$index), 'version' => 1])->assertOk()->assertJsonPath('data.code', 'T'.$index);
            $this->putJson('/'.$role.'/meja/'.$table->id, [...$this->details(' m01 '), 'version' => $table->fresh()->version])
                ->assertUnprocessable()->assertJsonPath('errors.code.0', self::DUPLICATE_MESSAGE);
            $this->assertSame('T'.$index, $table->fresh()->code);
        }
    }

    public function test_other_table_field_errors_are_readable_and_invalid_code_types_do_not_reach_the_unique_query(): void
    {
        $this->actingAs($this->createTableUser('karyawan'), 'web');
        $this->postJson('/karyawan/meja', [])->assertUnprocessable()->assertJsonPath('errors.code.0', 'Kode meja wajib diisi.')->assertDontSee('validation.');
        $this->postJson('/karyawan/meja', [...$this->details('M01'), 'code' => ['M01']])->assertUnprocessable()->assertJsonValidationErrors('code')->assertDontSee('validation.');
        $this->postJson('/karyawan/meja', [...$this->details('M01'), 'chair_positions' => ['left', 'outside']])->assertUnprocessable()->assertJsonValidationErrors('chair_positions.1')->assertDontSee('validation.');
        $this->assertDatabaseCount('coffee_tables',0);
    }
}
