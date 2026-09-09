<?php

namespace Pilot\Core\Support\Updates;

use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class PilotUpdateSafety
{
    /** @param array<string, string>|null $executables */
    public function __construct(
        protected Filesystem $files,
        protected ?string $applicationPath = null,
        protected ?string $pilotStoragePath = null,
        protected ?array $executables = null,
    ) {}

    /** @return list<string> */
    public function preflight(): array
    {
        $checks = [];

        foreach (['composer.json', 'composer.lock'] as $file) {
            $path = $this->basePath($file);

            if (! $this->files->exists($path) || ! is_writable($path)) {
                throw new RuntimeException("{$file} must exist and be writable before Pilot can update.");
            }
        }

        $checks[] = 'Composer files are writable';

        if (! is_writable($this->storagePath())) {
            throw new RuntimeException('The storage directory must be writable before Pilot can update.');
        }

        $minimumFreeMb = (int) config('cms.updates.minimum_free_mb', 512);
        $freeBytes = disk_free_space($this->basePath());

        if ($freeBytes !== false && $freeBytes < ($minimumFreeMb * 1024 * 1024)) {
            throw new RuntimeException("Pilot needs at least {$minimumFreeMb} MB of free disk space to update safely.");
        }

        $checks[] = 'Storage and disk space are ready';

        $this->requireExecutable('composer');

        if ($this->files->exists($this->basePath('package.json'))) {
            $this->requireExecutable('npm');
        }

        $checks[] = 'Required update tools are available';

        DB::connection()->select('select 1');
        $checks[] = 'Database connection is healthy';

        if (config('cms.updates.database_backup', true)) {
            $this->assertDatabaseBackupSupported();
            $checks[] = 'Database backup tooling is available';
        }

        return $checks;
    }

    /** @return array<string, mixed> */
    public function createBackup(string $updateId): array
    {
        $directory = $this->backupRoot().DIRECTORY_SEPARATOR.$updateId;
        $this->files->ensureDirectoryExists($directory, 0700, true);

        foreach (['composer.json', 'composer.lock'] as $file) {
            $this->files->copy($this->basePath($file), $directory.DIRECTORY_SEPARATOR.$file);
            @chmod($directory.DIRECTORY_SEPARATOR.$file, 0600);
        }

        $backup = [
            'id' => $updateId,
            'path' => $directory,
            'created_at' => now()->toIso8601String(),
            'database' => null,
        ];

        if (config('cms.updates.database_backup', true)) {
            $backup['database'] = $this->backupDatabase($directory);
        }

        $this->files->put(
            $directory.DIRECTORY_SEPARATOR.'backup.json',
            json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL,
            true,
        );
        @chmod($directory.DIRECTORY_SEPARATOR.'backup.json', 0600);
        $this->pruneBackups($updateId);

        return $backup;
    }

    /** @param array<string, mixed> $backup */
    public function rollback(array $backup, callable $log): void
    {
        $directory = (string) ($backup['path'] ?? '');

        if (! $this->validBackupDirectory($directory)) {
            throw new RuntimeException('The update backup directory is invalid. Automatic rollback was stopped.');
        }

        $log("Restoring Composer files…\n");

        foreach (['composer.json', 'composer.lock'] as $file) {
            $source = $directory.DIRECTORY_SEPARATOR.$file;

            if (! $this->files->exists($source)) {
                throw new RuntimeException("The backup is missing {$file}.");
            }

            $this->files->copy($source, $this->basePath($file));
        }

        $this->run(
            [PHP_BINARY, $this->requireExecutable('composer'), 'install', '--no-interaction', '--prefer-dist'],
            $log,
            'Composer dependencies could not be restored.',
        );

        if (is_array($backup['database'] ?? null)) {
            $log("Restoring the database…\n");
            $this->restoreDatabase($backup['database'], $log);
        }

        if ($this->files->exists($this->basePath('package.json'))) {
            $log("Rebuilding frontend assets…\n");
            $npm = $this->requireExecutable('npm');

            if ($this->files->exists($this->basePath('package-lock.json'))) {
                $this->run([$npm, 'ci'], $log, 'Frontend dependencies could not be restored.');
            }

            $this->run([$npm, 'run', 'build'], $log, 'Frontend assets could not be rebuilt.');
        }

        $this->run([PHP_BINARY, 'artisan', 'optimize:clear'], $log, 'Laravel caches could not be cleared after rollback.');
    }

    /** @return list<string> */
    public function postflight(string $target, callable $log): array
    {
        $checks = [];
        $process = $this->run(
            [PHP_BINARY, $this->requireExecutable('composer'), 'show', 'pilotcms/core', '--format=json', '--no-interaction'],
            $log,
            'Composer could not verify the installed Pilot version.',
        );
        $package = json_decode($process->getOutput(), true);
        $installed = ltrim((string) ($package['versions'][0] ?? $package['version'] ?? ''), 'v');

        if ($installed !== ltrim($target, 'v')) {
            throw new RuntimeException("Expected Pilot {$target}, but Composer reports ".($installed ?: 'an unknown version').'.');
        }

        $checks[] = "Composer reports Pilot {$installed}";
        DB::connection()->select('select 1');
        $checks[] = 'Database connection is healthy';

        $this->run([PHP_BINARY, 'artisan', 'route:list', '--except-vendor'], $log, 'Laravel could not load the application routes.');
        $checks[] = 'Application routes load successfully';

        return $checks;
    }

    public function checkHealthUrl(): ?string
    {
        $url = config('cms.updates.health_url');

        if (! is_string($url) || trim($url) === '') {
            return null;
        }

        $response = Http::timeout((int) config('cms.updates.health_timeout', 15))->get($url);

        if (! $response->successful()) {
            throw new RuntimeException("The post-update health URL returned HTTP {$response->status()}.");
        }

        return 'Health URL responded successfully';
    }

    public function checkApplicationResponse(): string
    {
        $path = (string) config('cms.updates.health_path', '/login');
        $kernel = app(HttpKernel::class);
        $request = Request::create('/'.ltrim($path, '/'), 'GET');
        $response = $kernel->handle($request);

        try {
            if ($response->getStatusCode() >= 400) {
                throw new RuntimeException("The internal post-update health check returned HTTP {$response->getStatusCode()} for {$path}.");
            }
        } finally {
            $kernel->terminate($request, $response);
        }

        return "Internal health path {$path} responded successfully";
    }

    private function assertDatabaseBackupSupported(): void
    {
        match ($this->databaseDriver()) {
            'sqlite' => null,
            'mysql', 'mariadb' => [$this->requireExecutable('mysqldump'), $this->requireExecutable('mysql')],
            'pgsql' => [$this->requireExecutable('pg_dump'), $this->requireExecutable('pg_restore')],
            default => throw new RuntimeException("Automatic database backups do not support the {$this->databaseDriver()} driver."),
        };
    }

    /** @return array<string, mixed> */
    private function backupDatabase(string $directory): array
    {
        $driver = $this->databaseDriver();

        if ($driver === 'sqlite') {
            $database = (string) DB::connection()->getDatabaseName();

            if ($database === ':memory:' || ! $this->files->exists($database)) {
                throw new RuntimeException('The SQLite database file could not be backed up.');
            }

            DB::statement('PRAGMA wal_checkpoint(FULL)');
            $dump = $directory.DIRECTORY_SEPARATOR.'database.sqlite';
            $this->files->copy($database, $dump);
            @chmod($dump, 0600);

            return ['driver' => 'sqlite', 'file' => $dump, 'database' => $database];
        }

        $connection = $this->connectionConfig();
        $database = (string) ($connection['database'] ?? '');
        $dump = $directory.DIRECTORY_SEPARATOR.($driver === 'pgsql' ? 'database.sql' : 'database.sql');

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $command = [
                $this->requireExecutable('mysqldump'),
                '--single-transaction',
                '--skip-lock-tables',
                '--no-tablespaces',
                '--result-file='.$dump,
                '--host='.(string) ($connection['host'] ?? '127.0.0.1'),
                '--port='.(string) ($connection['port'] ?? 3306),
                '--user='.(string) ($connection['username'] ?? ''),
                $database,
            ];
            $this->run($command, static function (): void {}, 'The MySQL database backup failed.', $this->databaseEnvironment($connection));
        } elseif ($driver === 'pgsql') {
            $command = [
                $this->requireExecutable('pg_dump'),
                '--format=custom',
                '--file='.$dump,
                '--host='.(string) ($connection['host'] ?? '127.0.0.1'),
                '--port='.(string) ($connection['port'] ?? 5432),
                '--username='.(string) ($connection['username'] ?? ''),
                $database,
            ];
            $this->run($command, static function (): void {}, 'The PostgreSQL database backup failed.', $this->databaseEnvironment($connection));
        } else {
            throw new RuntimeException("Automatic database backups do not support the {$driver} driver.");
        }

        @chmod($dump, 0600);

        return ['driver' => $driver, 'file' => $dump];
    }

    /** @param array<string, mixed> $database */
    private function restoreDatabase(array $database, callable $log): void
    {
        $driver = (string) ($database['driver'] ?? '');
        $dump = (string) ($database['file'] ?? '');

        if (! $this->files->exists($dump)) {
            throw new RuntimeException('The database backup file is missing.');
        }

        if ($driver === 'sqlite') {
            $destination = (string) ($database['database'] ?? '');

            if ($destination === '') {
                throw new RuntimeException('The SQLite restore destination is missing.');
            }

            DB::disconnect();
            $this->files->copy($dump, $destination);

            return;
        }

        $connection = $this->connectionConfig();
        $name = (string) ($connection['database'] ?? '');
        DB::disconnect();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $process = new Process([
                $this->requireExecutable('mysql'),
                '--host='.(string) ($connection['host'] ?? '127.0.0.1'),
                '--port='.(string) ($connection['port'] ?? 3306),
                '--user='.(string) ($connection['username'] ?? ''),
                $name,
            ], $this->basePath(), $this->databaseEnvironment($connection), null, null);
            $process->setInput(fopen($dump, 'r'));
            $this->runProcess($process, $log, 'The MySQL database restore failed.');

            return;
        }

        if ($driver === 'pgsql') {
            $this->run([
                $this->requireExecutable('pg_restore'),
                '--clean',
                '--if-exists',
                '--no-owner',
                '--host='.(string) ($connection['host'] ?? '127.0.0.1'),
                '--port='.(string) ($connection['port'] ?? 5432),
                '--username='.(string) ($connection['username'] ?? ''),
                '--dbname='.$name,
                $dump,
            ], $log, 'The PostgreSQL database restore failed.', $this->databaseEnvironment($connection));

            return;
        }

        throw new RuntimeException("Automatic database restore does not support the {$driver} driver.");
    }

    /** @param list<string> $command @param array<string, string> $environment */
    private function run(array $command, callable $log, string $failure, array $environment = []): Process
    {
        $process = new Process($command, $this->basePath(), $environment ?: null, null, null);
        $this->runProcess($process, $log, $failure);

        return $process;
    }

    private function runProcess(Process $process, callable $log, string $failure): void
    {
        $exitCode = $process->run(static function (string $type, string $output) use ($log): void {
            $log($output);
        });

        if ($exitCode !== 0) {
            throw new RuntimeException($failure.' '.trim($process->getErrorOutput()));
        }
    }

    private function requireExecutable(string $name): string
    {
        if (is_string($this->executables[$name] ?? null) && $this->executables[$name] !== '') {
            return $this->executables[$name];
        }

        $path = (new ExecutableFinder)->find($name);

        if (! is_string($path) || $path === '') {
            throw new RuntimeException("{$name} is required for safe Pilot updates but was not found on PATH.");
        }

        return $path;
    }

    private function databaseDriver(): string
    {
        return (string) DB::connection()->getDriverName();
    }

    /** @return array<string, mixed> */
    private function connectionConfig(): array
    {
        $name = (string) config('database.default');

        return (array) config("database.connections.{$name}", []);
    }

    /** @param array<string, mixed> $connection @return array<string, string> */
    private function databaseEnvironment(array $connection): array
    {
        $password = (string) ($connection['password'] ?? '');

        return match ($this->databaseDriver()) {
            'mysql', 'mariadb' => $password === '' ? [] : ['MYSQL_PWD' => $password],
            'pgsql' => $password === '' ? [] : ['PGPASSWORD' => $password],
            default => [],
        };
    }

    private function backupRoot(): string
    {
        return $this->storagePath('app/pilot/updates');
    }

    private function validBackupDirectory(string $directory): bool
    {
        $root = realpath($this->backupRoot());
        $resolved = realpath($directory);

        return $root !== false && $resolved !== false && str_starts_with($resolved.DIRECTORY_SEPARATOR, $root.DIRECTORY_SEPARATOR);
    }

    private function pruneBackups(string $currentId): void
    {
        $retention = max(1, (int) config('cms.updates.backup_retention', 3));
        $directories = collect($this->files->directories($this->backupRoot()))
            ->sortByDesc(static fn (string $path): int => filemtime($path) ?: 0)
            ->values();

        foreach ($directories->slice($retention) as $directory) {
            if (basename($directory) !== $currentId && $this->validBackupDirectory($directory)) {
                $this->files->deleteDirectory($directory);
            }
        }
    }

    private function basePath(string $path = ''): string
    {
        $base = $this->applicationPath ?? base_path();

        return $path === '' ? $base : $base.DIRECTORY_SEPARATOR.$path;
    }

    private function storagePath(string $path = ''): string
    {
        $storage = $this->pilotStoragePath ?? storage_path();

        return $path === '' ? $storage : $storage.DIRECTORY_SEPARATOR.$path;
    }
}
