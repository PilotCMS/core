<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();

            $table->unique(['space_id', 'slug']);
        });

        Schema::create('asset_asset_tag', function (Blueprint $table) {
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['asset_id', 'asset_tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_asset_tag');
        Schema::dropIfExists('asset_tags');
    }
};
