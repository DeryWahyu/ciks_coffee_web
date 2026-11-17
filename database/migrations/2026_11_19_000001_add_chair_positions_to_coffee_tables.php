<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coffee_tables', function (Blueprint $table) {
            $table->json('chair_positions')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('coffee_tables', function (Blueprint $table) {
            $table->dropColumn('chair_positions');
        });
    }
};
