<?php

namespace Pilot\Core\Support\Installation;

use Illuminate\Filesystem\Filesystem;

class InstallationState
{
    public function __construct(private readonly Filesystem $files) {}

    public function installed(): bool
    {
        if (app()->runningUnitTests() && config('installation.assume_installed_when_testing')) {
            return true;
        }

        return $this->files->exists($this->lockFile());
    }

    /** @param array<string, mixed> $metadata */
    public function markInstalled(array $metadata = []): void
    {
        $path = $this->lockFile();
        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, json_encode([
            'installed_at' => now()->toIso8601String(),
            ...$metadata,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    }

    public function lockFile(): string
    {
        return (string) config('installation.lock_file');
    }
}
