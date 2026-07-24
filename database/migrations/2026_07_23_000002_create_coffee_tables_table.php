<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('coffee_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('floor_layout_id')
                ->constrained()
                ->restrictOnDelete();
            $table->string('code', 30);
            $table->string('name');
            $table->unsignedSmallInteger('capacity')->default(2);
            $table->enum('shape', ['round', 'square', 'rectangle'])->default('round');
            $table->decimal('position_x', 5, 2);
            $table->decimal('position_y', 5, 2);
            $table->decimal('width', 5, 2)->default(12);
            $table->decimal('height', 5, 2)->default(12);
            $table->decimal('rotation', 5, 2)->default(0);
            $table->enum('status', ['available', 'occupied', 'reserved', 'unavailable'])
                ->default('available')
                ->index();
            $table->string('status_note', 500)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('status_updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('status_updated_at')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->unique(['floor_layout_id', 'code']);
            $table->index(['floor_layout_id', 'is_active', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coffee_tables');
    }
};
