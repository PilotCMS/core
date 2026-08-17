<?php

namespace Pilot\Core\Support\Installation;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

class EnvironmentFile
{
    public function __construct(private readonly Filesystem $files) {}

    /** @param array<string, string|int|bool|null> $values */
    public function write(array $values): void
    {
        $path = app()->environmentFilePath();

        if (! $this->files->exists($path)) {
            $example = base_path('.env.example');

            if (! $this->files->exists($example)) {
                throw new RuntimeException('Pilot could not find .env or .env.example.');
            }

            $this->files->copy($example, $path);
        }

        $contents = $this->files->get($path);

        foreach ($values as $key => $value) {
            $line = $key.'='.$this->encode($value);
            $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

            if (preg_match($pattern, $contents) === 1) {
                $contents = (string) preg_replace($pattern, $line, $contents);
            } else {
                $contents = rtrim($contents).PHP_EOL.$line.PHP_EOL;
            }
        }

        if ($this->files->put($path, $contents, true) === false) {
            throw new RuntimeException('Pilot could not write the environment file.');
        }
    }

    private function encode(string|int|bool|null $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $value = (string) $value;

        if ($value === '' || preg_match('/[\s#="\'\\$]/', $value) === 1) {
            $value = str_replace(
                ['\\', '"', '$', "\n", "\r", "\t"],
                ['\\\\', '\\"', '\\$', '\\n', '\\r', '\\t'],
                $value,
            );

            return '"'.$value.'"';
        }

        return $value;
    }
}
