<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Must drop FK constraints first before dropping unique index on MySQL
        Schema::table('ingredient_product', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['ingredient_id']);
            $table->dropUnique(['product_id', 'ingredient_id']);
        });

        Schema::table('ingredient_product', function (Blueprint $table) {
            // Add variant column
            $table->string('variant', 10)->nullable()->after('quantity');

            // Re-add FK constraints
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('ingredient_id')->references('id')->on('ingredients')->cascadeOnDelete();

            // New unique constraint including variant
            $table->unique(['product_id', 'ingredient_id', 'variant']);
        });
    }

    public function down(): void
    {
        Schema::table('ingredient_product', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['ingredient_id']);
            $table->dropUnique(['product_id', 'ingredient_id', 'variant']);
            $table->dropColumn('variant');

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('ingredient_id')->references('id')->on('ingredients')->cascadeOnDelete();
            $table->unique(['product_id', 'ingredient_id']);
        });
    }
};
