<?php

namespace Pilot\Core\Support\Installation;

use Illuminate\Filesystem\Filesystem;

class HostSynchronizer
{
    public function __construct(protected Filesystem $files) {}

    /** @return list<string> */
    public function sync(string $basePath): array
    {
        $changes = [];

        $this->removeTweakerAutoload($basePath.'/composer.json', $changes);

        $this->replace(
            $basePath.'/bootstrap/app.php',
            [
                "        api: __DIR__.'/../routes/api.php',\n",
                "        api: __DIR__.'/../routes/api.php',\r\n",
            ],
            '',
            $changes,
        );

        $this->replace(
            $basePath.'/bootstrap/app.php',
            ['use App\\Http\\Middleware\\EnsurePilotIsInstalled;'],
            'use Pilot\\Core\\Http\\Middleware\\EnsurePilotIsInstalled;',
            $changes,
        );

        $this->replace(
            $basePath.'/routes/web.php',
            ['use App\\Http\\Controllers\\Site\\PageController;'],
            'use Pilot\\Core\\Http\\Controllers\\Site\\PageController;',
            $changes,
        );

        $this->replace(
            $basePath.'/routes/console.php',
            [
                "use Illuminate\\Support\\Facades\\Schedule;\n",
                "use Illuminate\\Support\\Facades\\Schedule;\r\n",
                "Schedule::command('pilot:publish-scheduled')->everyMinute();\n",
                "Schedule::command('pilot:publish-scheduled')->everyMinute();\r\n",
            ],
            '',
            $changes,
        );

        $this->replace(
            $basePath.'/routes/web.php',
            [
                "require __DIR__.'/settings.php';\n",
                "require __DIR__.'/settings.php';\r\n",
            ],
            '',
            $changes,
        );

        $this->replace(
            $basePath.'/bootstrap/providers.php',
            [
                "    App\\Providers\\FortifyServiceProvider::class,\n",
                "    App\\Providers\\FortifyServiceProvider::class,\r\n",
            ],
            '',
            $changes,
        );

        $this->replace(
            $basePath.'/bootstrap/providers.php',
            [
                "    App\\Providers\\AppServiceProvider::class,\n",
                "    App\\Providers\\AppServiceProvider::class,\r\n",
            ],
            '',
            $changes,
        );

        $this->replace(
            $basePath.'/bootstrap/providers.php',
            [
                "    Tweaker\\TweakerServiceProvider::class,\n",
                "    Tweaker\\TweakerServiceProvider::class,\r\n",
                "use Tweaker\\TweakerServiceProvider;\n",
                "use Tweaker\\TweakerServiceProvider;\r\n",
                "    TweakerServiceProvider::class,\n",
                "    TweakerServiceProvider::class,\r\n",
            ],
            '',
            $changes,
        );

        $this->replace(
            $basePath.'/routes/web.php',
            [
                "require __DIR__.'/admin.php';\n",
                "require __DIR__.'/admin.php';\r\n",
            ],
            '',
            $changes,
        );

        $this->replace(
            $basePath.'/routes/web.php',
            [
                "require __DIR__.'/setup.php';\n",
                "require __DIR__.'/setup.php';\r\n",
            ],
            '',
            $changes,
        );

        $this->write(
            $basePath.'/resources/css/app.css',
            "@import '../../vendor/pilotcms/core/resources/css/jaunt/tokens/fonts.css';\n"
                ."@import 'tailwindcss';\n"
                ."@import '../../vendor/livewire/flux/dist/flux.css';\n"
                ."@import '../../vendor/pilotcms/core/resources/css/app.css';\n",
            $changes,
        );

        $this->write(
            $basePath.'/resources/js/app.js',
            "import '../../vendor/pilotcms/core/resources/js/app.js';\n",
            $changes,
        );

        $this->write(
            $basePath.'/package.json',
            $this->files->get(__DIR__.'/../../../resources/host/package.json'),
            $changes,
        );

        $this->write(
            $basePath.'/package-lock.json',
            $this->files->get(__DIR__.'/../../../resources/host/package-lock.json'),
            $changes,
        );

        return $changes;
    }

    /** @param list<string> $changes */
    protected function removeTweakerAutoload(string $path, array &$changes): void
    {
        if (! $this->files->exists($path)) {
            return;
        }

        $composer = json_decode($this->files->get($path), true, flags: JSON_THROW_ON_ERROR);

        if (! isset($composer['autoload']['psr-4']['Tweaker\\'])) {
            return;
        }

        unset($composer['autoload']['psr-4']['Tweaker\\']);

        $this->files->put(
            $path,
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL,
        );
        $changes[] = $this->relativePath($path);
    }

    /** @param list<string> $needles @param list<string> $changes */
    protected function replace(string $path, array $needles, string $replacement, array &$changes): void
    {
        if (! $this->files->exists($path)) {
            return;
        }

        $contents = $this->files->get($path);
        $updated = str_replace($needles, $replacement, $contents);

        if ($updated !== $contents) {
            $this->files->put($path, $updated);
            $changes[] = $this->relativePath($path);
        }
    }

    /** @param list<string> $changes */
    protected function write(string $path, string $contents, array &$changes): void
    {
        if ($this->files->exists($path) && $this->files->get($path) === $contents) {
            return;
        }

        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, $contents);
        $changes[] = $this->relativePath($path);
    }

    protected function relativePath(string $path): string
    {
        return str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
    }
}
