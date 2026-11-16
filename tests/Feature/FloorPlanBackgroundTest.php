<?php

namespace Tests\Feature;

use App\Models\FloorLayout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTableAvailabilityFixtures;
use Tests\TestCase;

class FloorPlanBackgroundTest extends TestCase
{
    use CreatesTableAvailabilityFixtures;
    use RefreshDatabase;

    public function test_background_matches_the_original_image_dimensions_and_is_available_to_all_roles(): void
    {
        $image = getimagesize(public_path(config('floor_plan.background.path')));
        $this->assertSame(3508, $image[0]);
        $this->assertSame(2481, $image[1]);
        $owner = $this->createTableUser(User::ROLE_PEMILIK);
        $response = $this->actingAs($owner)->getJson('/pemilik/meja/data')
            ->assertOk()
            ->assertJsonPath('data.layout.canvas_width', 3508)
            ->assertJsonPath('data.layout.canvas_height', 2481)
            ->assertJsonPath('data.layout.background_image.width', 3508)
            ->assertJsonPath('data.layout.background_image.height', 2481)
            ->assertJsonCount(0, 'data.tables');
        $background = $response->json('data.layout.background_image');
        $this->actingAs($this->createTableUser(User::ROLE_KARYAWAN))->getJson('/karyawan/meja/data')
            ->assertOk()->assertJsonPath('data.layout.background_image', $background);
        Sanctum::actingAs($this->createTableUser());
        $this->getJson('/api/table-layout')->assertOk()->assertJsonPath('data.layout.background_image', $background);
    }

    public function test_reference_canvas_size_can_be_saved_without_moving_existing_tables(): void
    {
        $layout = FloorLayout::query()->sole();
        $layout->update(['canvas_width' => 1200, 'canvas_height' => 800]);
        $table = $this->createCoffeeTable($layout);
        $before = $table->fresh()->getAttributes();
        $this->actingAs($this->createTableUser(User::ROLE_PEMILIK))
            ->putJson('/pemilik/meja/layout/'.$layout->id, [
                'canvas_width' => 3508, 'canvas_height' => 2481,
                'tables' => [$table->only(['id', 'position_x', 'position_y', 'width', 'height', 'rotation', 'version'])],
            ])->assertOk()->assertJsonPath('data.layout.canvas_width', 3508)
            ->assertJsonPath('data.layout.canvas_height', 2481);
        $this->assertSame($before, $table->fresh()->getAttributes());
    }

    public function test_background_alignment_preserves_populated_and_customized_layouts(): void
    {
        $empty = $this->createFloorLayout();
        $populated = $this->createFloorLayout();
        $this->createCoffeeTable($populated);
        $custom = $this->createFloorLayout(['background_config' => ['show_grid' => true, 'elements' => [
            ['type' => 'text', 'label' => 'Area khusus', 'position_x' => 50, 'position_y' => 50],
        ]]]);
        $resized = $this->createFloorLayout(['canvas_width' => 1600, 'canvas_height' => 1000]);
        $before = collect([$populated, $custom, $resized])->map(fn ($layout) => $layout->fresh()->getAttributes());

        $migration = require database_path('migrations/2026_11_04_000001_align_empty_floor_layout_with_background.php');
        $migration->up();
        $migration->up();

        $this->assertSame(3508, $empty->fresh()->canvas_width);
        $this->assertSame(2481, $empty->fresh()->canvas_height);
        $this->assertFalse($empty->fresh()->background_config['show_grid']);
        foreach ([$populated, $custom, $resized] as $index => $layout) {
            $this->assertSame($before[$index], $layout->fresh()->getAttributes());
        }
        $this->assertDatabaseCount('coffee_tables', 1);
    }
}
