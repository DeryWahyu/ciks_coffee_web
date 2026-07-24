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
        Schema::create('table_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coffee_table_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('old_status', 30);
            $table->string('new_status', 30);
            $table->string('note', 500)->nullable();
            $table->string('source', 50);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['coffee_table_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_status_histories');
    }
};
