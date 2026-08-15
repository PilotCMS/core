<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blocks', function (Blueprint $table) {
            $table->foreignId('reusable_source_block_id')->nullable()->after('parent_block_id')->constrained('blocks')->nullOnDelete();
            $table->string('reusable_key')->nullable()->after('type');
            $table->string('reusable_name')->nullable()->after('reusable_key');

            $table->index(['reusable_key']);
            $table->index(['reusable_source_block_id']);
        });
    }

    public function down(): void
    {
        Schema::table('blocks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reusable_source_block_id');
            $table->dropIndex(['reusable_key']);
            $table->dropColumn(['reusable_key', 'reusable_name']);
        });
    }
};
