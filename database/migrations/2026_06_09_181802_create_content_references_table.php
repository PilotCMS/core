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
        Schema::create('content_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained()->cascadeOnDelete();
            $table->foreignId('target_content_id')->constrained('contents')->cascadeOnDelete();
            $table->foreignId('block_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('field_key');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['content_id', 'target_content_id', 'block_id', 'field_key'], 'content_references_unique');
            $table->index(['target_content_id', 'field_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_references');
    }
};
