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
        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_block_id')->nullable()->constrained('blocks')->nullOnDelete();
            $table->string('type'); // References block_types.key
            $table->integer('position')->default(0);
            $table->json('data'); // Block field values
            $table->timestamps();

            $table->index(['content_id', 'parent_block_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocks');
    }
};
