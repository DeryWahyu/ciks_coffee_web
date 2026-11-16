<?php

namespace Tests\Feature;

use App\Models\CoffeeTable;
use App\Models\FloorLayout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTableAvailabilityFixtures;
use Tests\TestCase;

class FloorPlanEditorFeatureTest extends TestCase
{
    use CreatesTableAvailabilityFixtures;
    use RefreshDatabase;

    public function test_owner_can_save_a_complete_site_plan_before_adding_any_tables(): void
    {
        $layout = FloorLayout::query()->sole();
        $owner = $this->createTableUser(User::ROLE_PEMILIK);
        $elements = [];
        foreach (['area', 'rectangle', 'ellipse', 'line', 'wall', 'text', 'parking', 'counter', 'entrance', 'window', 'toilet', 'plant', 'stairs'] as $index => $type) {
            $elements[] = [
                'type' => $type, 'label' => $type === 'line' ? '' : 'Objek '.$index,
                'position_x' => 40, 'position_y' => 40, 'width' => 20, 'height' => 10,
                'rotation' => 90, 'fill' => 'stone', 'font_size' => 12,
            ];
        }

        $this->actingAs($owner)->putJson('/pemilik/meja/layout/'.$layout->id, [
            'name' => 'Seluruh Kedai', 'canvas_width' => 1600, 'canvas_height' => 1000,
            'background_config' => ['show_grid' => false, 'elements' => $elements],
            'tables' => [],
        ])->assertOk()->assertJsonPath('data.layout.canvas_width', 1600)
            ->assertJsonPath('data.layout.background_config.elements.6.type', 'parking')
            ->assertJsonPath('data.layout.background_config.elements.0.width', 20)
            ->assertJsonPath('data.layout.background_config.elements.0.rotation', 90)
            ->assertJsonCount(0, 'data.tables');

        $this->assertDatabaseCount('coffee_tables', 0);
        $this->assertCount(13, $layout->fresh()->background_config['elements']);
        $employee = $this->createTableUser(User::ROLE_KARYAWAN);
        $this->actingAs($employee)->getJson('/karyawan/meja/data')
            ->assertOk()->assertJsonCount(13, 'data.layout.background_config.elements');
    }

    public function test_background_only_edit_preserves_existing_operational_tables(): void
    {
        $layout = FloorLayout::query()->sole();
        $table = $this->createCoffeeTable($layout, ['status' => CoffeeTable::STATUS_OCCUPIED, 'version' => 4]);
        $original = $table->fresh()->getAttributes();

        $this->actingAs($this->createTableUser(User::ROLE_PEMILIK))
            ->putJson('/pemilik/meja/layout/'.$layout->id, [
                'background_config' => ['elements' => [['type' => 'text', 'label' => 'OUTDOOR', 'position_x' => 50, 'position_y' => 50]]],
                'tables' => [],
            ])->assertOk();

        $this->assertSame($original, $table->fresh()->getAttributes());
        $this->assertDatabaseCount('table_status_histories', 0);
    }

    public function test_editor_rejects_invalid_geometry_styles_and_unrecognized_element_fields(): void
    {
        $layout = FloorLayout::query()->sole();
        $before = $layout->fresh()->getAttributes();
        $this->actingAs($this->createTableUser(User::ROLE_PEMILIK))
            ->putJson('/pemilik/meja/layout/'.$layout->id, [
                'background_config' => ['elements' => [[
                    'type' => 'rectangle', 'label' => 'Area', 'position_x' => 50, 'position_y' => 50,
                    'width' => -10, 'height' => 101, 'rotation' => 500, 'fill' => 'url(javascript:alert(1))',
                    'font_size' => 200, 'html' => '<script>alert(1)</script>',
                ]]],
                'tables' => [],
            ])->assertUnprocessable()->assertJsonValidationErrors([
                'background_config.elements.0', 'background_config.elements.0.width',
                'background_config.elements.0.height', 'background_config.elements.0.rotation',
                'background_config.elements.0.fill', 'background_config.elements.0.font_size',
            ]);
        $this->assertSame($before, $layout->fresh()->getAttributes());
    }

    public function test_employee_cannot_save_drawings_even_without_table_changes(): void
    {
        $layout = FloorLayout::query()->sole();
        $this->actingAs($this->createTableUser(User::ROLE_KARYAWAN))
            ->putJson('/pemilik/meja/layout/'.$layout->id, [
                'background_config' => ['elements' => []], 'tables' => [],
            ])->assertForbidden();
    }
}
