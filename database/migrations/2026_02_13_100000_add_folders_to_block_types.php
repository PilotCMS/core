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
        Schema::create('block_type_folders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('block_type_folders')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('block_types', function (Blueprint $table) {
            $table->foreignId('folder_id')->nullable()->after('is_global')->constrained('block_type_folders')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('block_types', function (Blueprint $table) {
            $table->dropForeign(['folder_id']);
        });
        Schema::dropIfExists('block_type_folders');
    }
};
