<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->foreignId('reviewer_id')->nullable()->after('review_requested_by')->constrained('users')->nullOnDelete();
            $table->timestamp('review_due_at')->nullable()->after('reviewer_id');
            $table->text('review_note')->nullable()->after('review_due_at');
        });
    }

    public function down(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewer_id');
            $table->dropColumn(['review_due_at', 'review_note']);
        });
    }
};
