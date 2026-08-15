<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_presences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('selected_block_id')->nullable()->constrained('blocks')->nullOnDelete();
            $table->string('status')->default('viewing');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['content_id', 'user_id']);
            $table->index(['content_id', 'last_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_presences');
    }
};
