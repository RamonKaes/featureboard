<?php

declare(strict_types=1);

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
        Schema::table('feature_requests', function (Blueprint $table) {
            // Weights make a match in the title count more than one in the description.
            $table->tsvector('search_vector')->storedAs(
                "setweight(to_tsvector('german', title), 'A') || setweight(to_tsvector('german', description), 'B')",
            );

            $table->index('search_vector', algorithm: 'gin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feature_requests', function (Blueprint $table) {
            $table->dropColumn('search_vector');
        });
    }
};
