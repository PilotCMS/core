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
        Schema::table('contents', function (Blueprint $table) {
            $table->foreignId('content_type_id')->nullable()->after('parent_id')->constrained()->nullOnDelete();
            $table->string('workflow_status')->default('draft')->after('status');
            $table->timestamp('scheduled_for')->nullable()->after('published_at');
            $table->timestamp('review_requested_at')->nullable()->after('scheduled_for');
            $table->foreignId('review_requested_by')->nullable()->after('review_requested_at')->constrained('users')->nullOnDelete();
            $table->foreignId('published_revision_id')->nullable()->after('review_requested_by')->constrained('content_revisions')->nullOnDelete();

            $table->index(['space_id', 'content_type_id']);
            $table->index(['workflow_status', 'scheduled_for']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('content_type_id');
            $table->dropConstrainedForeignId('review_requested_by');
            $table->dropConstrainedForeignId('published_revision_id');
            $table->dropColumn([
                'workflow_status',
                'scheduled_for',
                'review_requested_at',
            ]);
        });
    }
};
