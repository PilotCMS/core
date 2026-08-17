<?php

namespace Pilot\Core\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class UpdatePilot extends Command
{
    protected $signature = 'pilot:update
        {--dry-run : Show whether an update is available without changing files}
        {--no-build : Skip the frontend production build}
        {--target= : Update to the release line containing this version}
        {--force : Continue when composer.json or composer.lock has uncommitted changes}';

    protected $description = 'Update Pilot Core and run its post-update steps';

    public function handle(): int
    {
        if (! $this->option('force') && $this->composerFilesAreDirty()) {
            $this->error('composer.json or composer.lock has uncommitted changes. Commit them or use --force.');

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            return $this->runStep([PHP_BINARY, $this->composerBinary(), 'outdated', 'pilotcms/core', '--direct'], 'Checking Pilot Core');
        }

        $composerCommand = $this->composerUpdateCommand();

        if ($this->runStep($composerCommand, 'Updating Pilot Core') !== self::SUCCESS) {
            return self::FAILURE;
        }

        $finalize = [PHP_BINARY, 'artisan', 'pilot:finalize-update'];

        if ($this->option('no-build')) {
            $finalize[] = '--no-build';
        }

        if ($this->runStep($finalize, 'Finalizing Pilot update') !== self::SUCCESS) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /** @return list<string> */
    private function composerUpdateCommand(): array
    {
        $command = [PHP_BINARY, $this->composerBinary()];
        $target = $this->option('target');

        if (is_string($target) && $target !== '') {
            $version = ltrim($target, 'v');

            if (! preg_match('/^\d+\.\d+\.\d+$/', $version)) {
                throw new \InvalidArgumentException('The target must be a stable semantic version, such as v0.3.0.');
            }

            [$major, $minor] = array_map('intval', explode('.', $version));
            $constraint = $major === 0 ? "^0.{$minor}" : "^{$major}.{$minor}";

            return [...$command, 'require', "pilotcms/core:{$constraint}", '--with-all-dependencies', '--no-interaction'];
        }

        return [...$command, 'update', 'pilotcms/core', '--with-all-dependencies', '--no-interaction'];
    }

    private function composerFilesAreDirty(): bool
    {
        if (! is_dir(base_path('.git'))) {
            return false;
        }

        $process = new Process(['git', 'status', '--porcelain', '--', 'composer.json', 'composer.lock'], base_path());
        $process->run();

        return $process->isSuccessful() && trim($process->getOutput()) !== '';
    }

    private function composerBinary(): string
    {
        $process = Process::fromShellCommandline('command -v composer', base_path());
        $process->run();

        $binary = trim($process->getOutput());

        if (! $process->isSuccessful() || $binary === '') {
            throw new \RuntimeException('Composer is not available on PATH.');
        }

        return $binary;
    }

    /** @param list<string> $command */
    private function runStep(array $command, string $label): int
    {
        $successful = false;

        $this->components->task($label, function () use ($command, &$successful): void {
            $process = new Process($command, base_path(), timeout: null);
            $process->setTty(Process::isTtySupported());
            $successful = $process->run(function (string $type, string $output): void {
                $this->output->write($output);
            }) === 0;
        });

        return ($successful ?? false) ? self::SUCCESS : self::FAILURE;
    }
}
