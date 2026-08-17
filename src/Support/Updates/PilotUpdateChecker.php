<?php

namespace Pilot\Core\Support\Updates;

use Composer\InstalledVersions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class PilotUpdateChecker
{
    private const CACHE_KEY = 'pilot.core.latest-release';

    /** @return array{installed: string, latest: ?string, update_available: bool, checked_at: string, error: ?string} */
    public function status(bool $refresh = false): array
    {
        if ($refresh) {
            $this->forget();
        }

        $installed = InstalledVersions::getPrettyVersion('pilotcms/core') ?? 'unknown';
        $release = Cache::get(self::CACHE_KEY);

        if (! is_array($release)) {
            try {
                $response = Http::acceptJson()
                    ->withUserAgent('PilotCMS/'.ltrim($installed, 'v'))
                    ->timeout(5)
                    ->get((string) config('cms.updates.api_url'))
                    ->throw();

                $latest = $response->json('tag_name');

                if (! is_string($latest) || ! preg_match('/^v?\d+\.\d+\.\d+$/', $latest)) {
                    throw new \RuntimeException('The release service returned an invalid version.');
                }

                $release = ['latest' => $latest, 'checked_at' => now()->toIso8601String()];
                Cache::put(self::CACHE_KEY, $release, max(60, (int) config('cms.updates.cache_ttl', 3600)));
            } catch (Throwable $exception) {
                return [
                    'installed' => $installed,
                    'latest' => null,
                    'update_available' => false,
                    'checked_at' => now()->toIso8601String(),
                    'error' => $exception->getMessage(),
                ];
            }
        }

        $latest = $release['latest'];

        return [
            'installed' => $installed,
            'latest' => $latest,
            'update_available' => $this->isNewer($latest, $installed),
            'checked_at' => $release['checked_at'],
            'error' => null,
        ];
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function isNewer(string $latest, string $installed): bool
    {
        $installed = ltrim($installed, 'v');
        $latest = ltrim($latest, 'v');

        return preg_match('/^\d+\.\d+\.\d+$/', $installed) === 1
            && version_compare($latest, $installed, '>');
    }
}
