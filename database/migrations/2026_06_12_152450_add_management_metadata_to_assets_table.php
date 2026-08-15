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
        Schema::table('assets', function (Blueprint $table) {
            $table->text('description')->nullable()->after('display_name');
            $table->string('credit')->nullable()->after('title');
            $table->string('copyright')->nullable()->after('credit');
            $table->string('license')->nullable()->after('copyright');
            $table->string('source_url')->nullable()->after('license');
            $table->string('checksum', 64)->nullable()->after('source_url');
            $table->timestamp('expires_at')->nullable()->after('checksum');
            $table->json('metadata')->nullable()->after('expires_at');

            $table->index('checksum');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropIndex(['checksum']);
            $table->dropIndex(['expires_at']);
            $table->dropColumn([
                'description',
                'credit',
                'copyright',
                'license',
                'source_url',
                'checksum',
                'expires_at',
                'metadata',
            ]);
        });
    }
};
