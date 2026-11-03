<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('feature_requests', function (Blueprint $table) {
            $table->id();
            // A product with requests must not vanish by accident.
            $table->foreignId('product_id')->index()->constrained()->restrictOnDelete();
            // Requests outlive their author, so account deletion keeps them anonymously.
            $table->foreignId('user_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description');
            // Values are repeated here on purpose: a migration must not change when the enum does.
            $table->enum('status', ['new', 'planned', 'in_progress', 'done', 'declined'])->default('new');
            $table->unsignedInteger('votes_count')->default(0);
            $table->timestamps();
        });

        // PostgreSQL has no unsigned integers, so only a check prevents negative counts.
        DB::statement('ALTER TABLE feature_requests ADD CONSTRAINT feature_requests_votes_count_not_negative CHECK (votes_count >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_requests');
    }
};
