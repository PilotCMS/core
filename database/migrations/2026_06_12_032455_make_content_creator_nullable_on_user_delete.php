<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->dropForeign(['space_id']);
            $table->dropForeign(['created_by']);

            $table->foreign('space_id')->references('id')->on('spaces')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->change();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreignId('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('editor_preferences', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreignId('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $fallbackUserId = DB::table('users')->orderBy('id')->value('id');

        if ($fallbackUserId !== null) {
            DB::table('contents')
                ->whereNull('created_by')
                ->update(['created_by' => $fallbackUserId]);

            DB::table('activities')
                ->whereNull('user_id')
                ->update(['user_id' => $fallbackUserId]);

            DB::table('editor_preferences')
                ->whereNull('user_id')
                ->update(['user_id' => $fallbackUserId]);
        }

        Schema::table('editor_preferences', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreignId('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreignId('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('contents', function (Blueprint $table) {
            $table->dropForeign(['space_id']);
            $table->dropForeign(['created_by']);

            $table->foreign('space_id')->references('id')->on('spaces')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable(false)->change();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
