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
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            // The unique index below also serves lookups by user, its first column.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feature_request_id')->index()->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'feature_request_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
