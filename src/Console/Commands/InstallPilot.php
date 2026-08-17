<?php

namespace Pilot\Core\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Pilot\Core\Support\Installation\PilotInstaller;

class InstallPilot extends Command
{
    protected $signature = 'pilot:install {--force : Run migrations in production without confirmation}';

    protected $description = 'Install Pilot and create the first administrator account';

    public function handle(): int
    {
        $this->components->info('Installing Pilot');

        try {
            $installer = app(PilotInstaller::class);
            $installer->prepareDatabase((bool) $this->option('force'));
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $admin = User::role('Admin')->oldest()->first();
        $createdAdministrator = false;

        if ($admin === null) {
            if (! $this->input->isInteractive()) {
                $this->components->error('Pilot requires an interactive terminal to create the first administrator account.');

                return self::FAILURE;
            }

            $admin = $this->createAdministrator();
            $createdAdministrator = true;
        } else {
            $this->components->warn("Pilot already has an administrator ({$admin->email}); account creation was skipped.");
        }

        if (! $createdAdministrator) {
            $installer->seedSpace($admin);
        }
        $installer->finish($admin);

        $this->newLine();
        $this->components->info("Pilot is ready. Sign in with {$admin->email}.");

        return self::SUCCESS;
    }

    private function createAdministrator(): User
    {
        $this->components->info('Create your administrator account');

        $name = $this->validatedAnswer(
            'Administrator name',
            'name',
            ['required', 'string', 'max:255'],
        );
        $email = strtolower($this->validatedAnswer(
            'Email address',
            'email',
            ['required', 'email', 'max:255', 'unique:users,email'],
        ));
        $password = $this->validatedPassword();

        return app(PilotInstaller::class)->createAdministrator([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);
    }

    /**
     * @param  array<int, mixed>  $rules
     */
    private function validatedAnswer(string $question, string $field, array $rules): string
    {
        while (true) {
            $answer = trim((string) $this->ask($question));
            $validator = Validator::make([$field => $answer], [$field => $rules]);

            if ($validator->passes()) {
                return $answer;
            }

            $this->components->error($validator->errors()->first($field));
        }
    }

    private function validatedPassword(): string
    {
        while (true) {
            $password = (string) $this->secret('Password');
            $confirmation = (string) $this->secret('Confirm password');
            $validator = Validator::make(
                ['password' => $password, 'password_confirmation' => $confirmation],
                ['password' => ['required', 'string', Password::defaults(), 'confirmed']],
            );

            if ($validator->passes()) {
                return $password;
            }

            $this->components->error($validator->errors()->first('password'));
        }
    }
}
