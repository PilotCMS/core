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
        Schema::create('datasource_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('datasource_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->json('value')->nullable(); // Translatable optional
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index(['datasource_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('datasource_entries');
    }
};
