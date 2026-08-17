<?php

namespace Pilot\Core\Support\Updates;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Symfony\Component\Process\Process;

class PilotUpdateManager
{
    public function __construct(protected Filesystem $files) {}

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

            if ($this->composerFilesAreDirty()) {
                throw new RuntimeException('composer.json or composer.lock has uncommitted changes. Commit them before updating.');
            }

            $state = [
                'status' => 'queued',
                'target' => $target,
                'message' => 'Waiting for the updater to start…',
                'initiated_by' => $initiatedBy,
                'started_at' => now()->toIso8601String(),
                'finished_at' => null,
            ];

            $this->writeState($state);
            $this->files->put($this->logPath(), '');

            $command = sprintf(
                'nohup %s %s pilot:update-background --target=%s --initiated-by=%s > /dev/null 2>&1 &',
                escapeshellarg(PHP_BINARY),
                escapeshellarg(base_path('artisan')),
                escapeshellarg($target),
                escapeshellarg((string) $initiatedBy),
            );
            $process = Process::fromShellCommandline($command, base_path(), timeout: 10);
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
        return $this->writeState([
            'status' => 'running',
            'target' => $target,
            'message' => 'Updating dependencies, migrating, and rebuilding assets…',
            'initiated_by' => $initiatedBy,
            'started_at' => $this->status()['started_at'] ?? now()->toIso8601String(),
            'finished_at' => null,
        ]);
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
        if (! $this->files->exists($this->logPath())) {
            return '';
        }

        $log = $this->files->get($this->logPath());

        return mb_substr(preg_replace('/\e\[[\d;]*m/', '', $log) ?? $log, -12000);
    }

    private function composerFilesAreDirty(): bool
    {
        if (! file_exists(base_path('.git'))) {
            return false;
        }

        $process = new Process(['git', 'status', '--porcelain', '--', 'composer.json', 'composer.lock'], base_path());
        $process->run();

        return $process->isSuccessful() && trim($process->getOutput()) !== '';
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
        return storage_path('app/pilot/update.json');
    }

    private function logPath(): string
    {
        $path = storage_path('logs/pilot-update.log');
        $this->files->ensureDirectoryExists(dirname($path));

        return $path;
    }
}
