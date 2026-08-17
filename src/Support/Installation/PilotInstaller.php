<?php

namespace Pilot\Core\Support\Installation;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Pilot\Core\Database\Seeders\DatabaseSeeder;
use Pilot\Core\Database\Seeders\SpaceSeeder;
use RuntimeException;
use Throwable;

class PilotInstaller
{
    public function __construct(
        private readonly EnvironmentFile $environment,
        private readonly InstallationState $state,
    ) {}

    /** @param array{connection:string,host?:string,port?:int|string,database:string,username?:string,password?:string} $credentials */
    public function configureDatabase(array $credentials): void
    {
        $connection = $credentials['connection'];
        $configuration = $connection === 'sqlite'
            ? ['database.connections.sqlite.database' => $credentials['database']]
            : [
                "database.connections.{$connection}.host" => $credentials['host'] ?? '127.0.0.1',
                "database.connections.{$connection}.port" => $credentials['port'] ?? ($connection === 'pgsql' ? 5432 : 3306),
                "database.connections.{$connection}.database" => $credentials['database'],
                "database.connections.{$connection}.username" => $credentials['username'] ?? '',
                "database.connections.{$connection}.password" => $credentials['password'] ?? '',
            ];

        config(['database.default' => $connection, ...$configuration]);
        DB::purge($connection);

        try {
            DB::connection($connection)->getPdo();
        } catch (Throwable $exception) {
            DB::purge($connection);

            throw new RuntimeException('Pilot could not connect to that database: '.$exception->getMessage(), previous: $exception);
        }

        $environmentValues = ['DB_CONNECTION' => $connection];

        if ($connection === 'sqlite') {
            $environmentValues['DB_DATABASE'] = $credentials['database'];
        } else {
            $environmentValues += [
                'DB_HOST' => $credentials['host'] ?? '127.0.0.1',
                'DB_PORT' => $credentials['port'] ?? ($connection === 'pgsql' ? 5432 : 3306),
                'DB_DATABASE' => $credentials['database'],
                'DB_USERNAME' => $credentials['username'] ?? '',
                'DB_PASSWORD' => $credentials['password'] ?? '',
            ];
        }

        $this->environment->write($environmentValues);
    }

    public function prepareDatabase(bool $force = false): void
    {
        $exitCode = Artisan::call('migrate', ['--force' => $force]);

        if ($exitCode !== 0) {
            throw new RuntimeException(trim(Artisan::output()) ?: 'Pilot could not run its database migrations.');
        }

        app(DatabaseSeeder::class)->run();
    }

    /** @param array{name:string,email:string,password:string} $attributes */
    public function createAdministrator(array $attributes): User
    {
        return DB::transaction(function () use ($attributes): User {
            $admin = User::create([
                'name' => $attributes['name'],
                'email' => strtolower($attributes['email']),
                'password' => $attributes['password'],
            ]);

            $admin->forceFill(['email_verified_at' => now()])->save();
            $admin->assignRole('Admin');
            $this->seedSpace($admin);

            return $admin;
        });
    }

    public function seedSpace(User $admin): void
    {
        app(SpaceSeeder::class)->run($admin);
    }

    /** @param array<string, string|int|bool|null> $values */
    public function configureProject(array $values): void
    {
        $this->environment->write($values);
    }

    public function finish(User $admin): void
    {
        $this->state->markInstalled([
            'version' => config('app.version'),
            'administrator_id' => $admin->getKey(),
        ]);
    }
}
