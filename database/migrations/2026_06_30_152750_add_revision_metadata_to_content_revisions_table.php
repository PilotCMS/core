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
        Schema::table('content_revisions', function (Blueprint $table) {
            $table->string('revision_type')->default('manual')->after('label');
            $table->foreignId('source_revision_id')->nullable()->after('revision_type')->constrained('content_revisions')->nullOnDelete();
            $table->json('meta')->nullable()->after('source_revision_id');

            $table->index(['content_id', 'revision_type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_revisions', function (Blueprint $table) {
            $table->dropIndex(['content_id', 'revision_type', 'created_at']);
            $table->dropConstrainedForeignId('source_revision_id');
            $table->dropColumn(['revision_type', 'meta']);
        });
    }
};
