<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var array<string, string> */
    private const MODEL_TYPES = [
        'App\\Models\\Activity' => 'Pilot\\Core\\Models\\Activity',
        'App\\Models\\Asset' => 'Pilot\\Core\\Models\\Asset',
        'App\\Models\\AssetFolder' => 'Pilot\\Core\\Models\\AssetFolder',
        'App\\Models\\AssetTag' => 'Pilot\\Core\\Models\\AssetTag',
        'App\\Models\\Block' => 'Pilot\\Core\\Models\\Block',
        'App\\Models\\BlockComment' => 'Pilot\\Core\\Models\\BlockComment',
        'App\\Models\\BlockType' => 'Pilot\\Core\\Models\\BlockType',
        'App\\Models\\BlockTypeFolder' => 'Pilot\\Core\\Models\\BlockTypeFolder',
        'App\\Models\\CmsSetting' => 'Pilot\\Core\\Models\\CmsSetting',
        'App\\Models\\Content' => 'Pilot\\Core\\Models\\Content',
        'App\\Models\\ContentPresence' => 'Pilot\\Core\\Models\\ContentPresence',
        'App\\Models\\ContentReference' => 'Pilot\\Core\\Models\\ContentReference',
        'App\\Models\\ContentRevision' => 'Pilot\\Core\\Models\\ContentRevision',
        'App\\Models\\ContentType' => 'Pilot\\Core\\Models\\ContentType',
        'App\\Models\\Datasource' => 'Pilot\\Core\\Models\\Datasource',
        'App\\Models\\DatasourceEntry' => 'Pilot\\Core\\Models\\DatasourceEntry',
        'App\\Models\\EditorPreference' => 'Pilot\\Core\\Models\\EditorPreference',
        'App\\Models\\Locale' => 'Pilot\\Core\\Models\\Locale',
        'App\\Models\\Redirect' => 'Pilot\\Core\\Models\\Redirect',
        'App\\Models\\Space' => 'Pilot\\Core\\Models\\Space',
        'App\\Models\\SpacePreviewTarget' => 'Pilot\\Core\\Models\\SpacePreviewTarget',
    ];

    public function up(): void
    {
        foreach (self::MODEL_TYPES as $legacyType => $coreType) {
            DB::table('activities')
                ->where('subject_type', $legacyType)
                ->update(['subject_type' => $coreType]);
        }
    }

    public function down(): void
    {
        foreach (self::MODEL_TYPES as $legacyType => $coreType) {
            DB::table('activities')
                ->where('subject_type', $coreType)
                ->update(['subject_type' => $legacyType]);
        }
    }
};
