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
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('content_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source');
            $table->string('destination');
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_hit_at')->nullable();
            $table->unsignedBigInteger('hit_count')->default(0);
            $table->timestamps();

            $table->unique(['space_id', 'source']);
            $table->index(['is_active', 'source']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('redirects');
    }
};
