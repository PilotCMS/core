<?php

namespace Pilot\Core\Support\Updates;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class PilotUpdateManager
{
    public function __construct(
        protected Filesystem $files,
        protected ?string $applicationPath = null,
        protected ?string $pilotStoragePath = null,
    ) {}

    /** @return array<string, mixed> */
    public function status(): array
    {
        if (! $this->files->exists($this->statePath())) {
            return ['status' => 'idle', 'message' => null];
        }

        $state = json_decode($this->files->get($this->statePath()), true);

        if (! is_array($state)) {
            return ['status' => 'idle', 'message' => null];
        }

        if (($state['status'] ?? null) === 'queued'
            && isset($state['started_at'])
            && Carbon::parse($state['started_at'])->addSeconds(30)->isPast()) {
            return $this->finish(false, 'The updater did not start. Check the launcher log and the configured PHP CLI executable.');
        }

        if (in_array($state['status'] ?? null, ['queued', 'running'], true)
            && isset($state['started_at'])
            && Carbon::parse($state['started_at'])->addSeconds((int) config('cms.updates.stale_after', 3600))->isPast()) {
            return $this->finish(false, 'The updater stopped before reporting completion. Check the update log.');
        }

        return $state;
    }

    /** @return array<string, mixed> */
    public function start(string $target, int|string|null $initiatedBy = null): array
    {
        if (! config('cms.updates.self_update', false)) {
            throw new RuntimeException('One-click updates are disabled for this environment.');
        }

        if (PHP_OS_FAMILY === 'Windows') {
            throw new RuntimeException('One-click updates are not currently supported on Windows hosts.');
        }

        if (! preg_match('/^v?\d+\.\d+\.\d+$/', $target)) {
            throw new RuntimeException('Pilot could not determine a valid target release.');
        }

        return Cache::lock('pilot.core.start-update', 10)->block(3, function () use ($target, $initiatedBy): array {
            if (in_array($this->status()['status'] ?? null, ['queued', 'running'], true)) {
                throw new RuntimeException('A Pilot update is already running.');
            }

            $this->assertComposerFilesAreSafe();

            $php = $this->cliPhpBinary();
            $composerFiles = $this->composerHashes();

            $state = [
                'status' => 'queued',
                'update_id' => (string) Str::uuid(),
                'target' => $target,
                'message' => 'Waiting for the updater to start…',
                'initiated_by' => $initiatedBy,
                'started_at' => now()->toIso8601String(),
                'finished_at' => null,
                'preflight_composer_files' => $composerFiles,
                'checks' => [],
                'backup' => null,
                'rollback' => null,
            ];

            $this->writeState($state);
            $this->files->put($this->logPath(), '');
            $this->files->put($this->launcherLogPath(), '');

            $command = sprintf(
                'nohup %s %s pilot:update-background --target=%s --initiated-by=%s < /dev/null > %s 2>&1 &',
                escapeshellarg($php),
                escapeshellarg($this->basePath('artisan')),
                escapeshellarg($target),
                escapeshellarg((string) $initiatedBy),
                escapeshellarg($this->launcherLogPath()),
            );
            $process = Process::fromShellCommandline($command, $this->basePath(), timeout: 10);
            $process->run();

            if (! $process->isSuccessful()) {
                $this->finish(false, 'Pilot could not start the background updater.');
                throw new RuntimeException('Pilot could not start the background updater.');
            }

            return $state;
        });
    }

    /** @return array<string, mixed> */
    public function running(string $target, int|string|null $initiatedBy = null): array
    {
        return $this->writeState(array_merge($this->statusWithoutExpiry(), [
            'status' => 'running',
            'target' => $target,
            'message' => 'Updating dependencies, migrating, and rebuilding assets…',
            'initiated_by' => $initiatedBy,
            'started_at' => $this->status()['started_at'] ?? now()->toIso8601String(),
            'finished_at' => null,
        ]));
    }

    /** @param array<string, mixed> $details @return array<string, mixed> */
    public function progress(string $message, array $details = []): array
    {
        return $this->writeState(array_merge($this->statusWithoutExpiry(), $details, [
            'status' => 'running',
            'message' => $message,
        ]));
    }

    public function assertComposerFilesUnchanged(): void
    {
        $expected = $this->statusWithoutExpiry()['preflight_composer_files'] ?? null;

        if (! is_array($expected) || ! $this->hashesMatch($expected, $this->composerHashes())) {
            throw new RuntimeException('composer.json or composer.lock changed after this update was requested. The update was stopped.');
        }
    }

    public function markComposerFilesAsUpdaterOwned(): void
    {
        $record = [
            'updated_at' => now()->toIso8601String(),
            'files' => $this->composerHashes(),
        ];

        $this->files->ensureDirectoryExists(dirname($this->ownershipPath()));
        $this->files->put($this->ownershipPath(), json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL, true);
    }

    /** @return array<string, mixed> */
    public function finish(bool $successful, string $message): array
    {
        $state = $this->statusWithoutExpiry();
        $state['status'] = $successful ? 'succeeded' : 'failed';
        $state['message'] = $message;
        $state['finished_at'] = now()->toIso8601String();

        return $this->writeState($state);
    }

    public function appendLog(string $output): void
    {
        $this->files->append($this->logPath(), $output);
    }

    public function log(): string
    {
        $log = '';

        foreach ([$this->logPath(), $this->launcherLogPath()] as $path) {
            if ($this->files->exists($path)) {
                $log .= $this->files->get($path);
            }
        }

        return mb_substr(preg_replace('/\e\[[\d;]*m/', '', $log) ?? $log, -12000);
    }

    public function assertComposerFilesAreSafe(): void
    {
        $gitAvailable = file_exists($this->basePath('.git'));

        if ($gitAvailable && ! $this->composerFilesAreDirty()) {
            return;
        }

        $owned = $this->readOwnership()['files'] ?? null;

        if (is_array($owned) && $this->hashesMatch($owned, $this->composerHashes())) {
            return;
        }

        if (! $gitAvailable && ! is_array($owned)) {
            return;
        }

        throw new RuntimeException('composer.json or composer.lock has changes that were not made by the Pilot updater. Commit or revert them before updating; on a host without Git, review the changes and run pilot update from the CLI once.');
    }

    private function composerFilesAreDirty(): bool
    {
        if (! file_exists($this->basePath('.git'))) {
            return false;
        }

        $process = new Process(['git', 'status', '--porcelain', '--', 'composer.json', 'composer.lock'], $this->basePath());
        $process->run();

        return $process->isSuccessful() && trim($process->getOutput()) !== '';
    }

    /** @return array<string, string> */
    private function composerHashes(): array
    {
        $hashes = [];

        foreach (['composer.json', 'composer.lock'] as $file) {
            $path = $this->basePath($file);

            if (! $this->files->exists($path)) {
                throw new RuntimeException("{$file} is missing.");
            }

            $hash = hash_file('sha256', $path);

            if (! is_string($hash)) {
                throw new RuntimeException("{$file} could not be fingerprinted.");
            }

            $hashes[$file] = $hash;
        }

        return $hashes;
    }

    /** @return array<string, mixed> */
    private function readOwnership(): array
    {
        if (! $this->files->exists($this->ownershipPath())) {
            return [];
        }

        $record = json_decode($this->files->get($this->ownershipPath()), true);

        return is_array($record) ? $record : [];
    }

    /** @param array<mixed> $first @param array<mixed> $second */
    private function hashesMatch(array $first, array $second): bool
    {
        $firstJson = json_encode($first);
        $secondJson = json_encode($second);

        return is_string($firstJson) && is_string($secondJson) && hash_equals($firstJson, $secondJson);
    }

    /** @param array<string, mixed> $state @return array<string, mixed> */
    private function writeState(array $state): array
    {
        $this->files->ensureDirectoryExists(dirname($this->statePath()));
        $this->files->put($this->statePath(), json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL, true);

        return $state;
    }

    /** @return array<string, mixed> */
    private function statusWithoutExpiry(): array
    {
        if (! $this->files->exists($this->statePath())) {
            return [];
        }

        $state = json_decode($this->files->get($this->statePath()), true);

        return is_array($state) ? $state : [];
    }

    private function statePath(): string
    {
        return $this->storagePath('app/pilot/update.json');
    }

    private function logPath(): string
    {
        $path = $this->storagePath('logs/pilot-update.log');
        $this->files->ensureDirectoryExists(dirname($path));

        return $path;
    }

    private function launcherLogPath(): string
    {
        return $this->storagePath('logs/pilot-update-launcher.log');
    }

    private function cliPhpBinary(): string
    {
        $php = config('cms.updates.php_binary') ?: (new PhpExecutableFinder)->find(false);

        if (! is_string($php) || $php === '' || ! is_executable($php) || is_dir($php)) {
            throw new RuntimeException('A PHP CLI executable is required. Set PILOT_UPDATE_PHP_BINARY to its absolute path.');
        }

        $probe = new Process([$php, '-r', 'echo PHP_SAPI."|".PHP_VERSION_ID;'], $this->basePath(), timeout: 10);
        $probe->run();
        $result = explode('|', trim($probe->getOutput()));

        if (! $probe->isSuccessful() || ($result[0] ?? '') !== 'cli' || (int) ($result[1] ?? 0) < 80401) {
            throw new RuntimeException('Pilot updates require PHP CLI 8.4.1 or newer. Check PILOT_UPDATE_PHP_BINARY.');
        }

        return $php;
    }

    private function ownershipPath(): string
    {
        return $this->storagePath('app/pilot/composer-ownership.json');
    }

    private function basePath(string $path = ''): string
    {
        $base = $this->applicationPath ?? base_path();

        return $path === '' ? $base : $base.DIRECTORY_SEPARATOR.$path;
    }

    private function storagePath(string $path): string
    {
        $storage = $this->pilotStoragePath ?? storage_path();

        return $storage.DIRECTORY_SEPARATOR.$path;
    }
}
