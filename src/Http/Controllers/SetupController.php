<?php

namespace Pilot\Core\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Pilot\Core\Support\Installation\InstallationState;
use Pilot\Core\Support\Installation\PilotInstaller;
use Throwable;

class SetupController extends Controller
{
    private const STEPS = ['welcome', 'requirements', 'database', 'account', 'project', 'developer'];

    public function __construct(
        private readonly InstallationState $state,
        private readonly PilotInstaller $installer,
    ) {}

    public function show(Request $request, string $step = 'welcome'): View|RedirectResponse
    {
        abort_if($this->state->installed(), 404);
        abort_unless(in_array($step, self::STEPS, true), 404);

        if (in_array($step, ['account', 'project', 'developer'], true) && ! $request->session()->get('pilot_setup.database_ready')) {
            return redirect()->route('setup.show', ['step' => 'database']);
        }

        if (in_array($step, ['project', 'developer'], true) && ! $request->session()->get('pilot_setup.admin_id')) {
            return redirect()->route('setup.show', ['step' => 'account']);
        }

        if ($step === 'account' && $request->session()->get('pilot_setup.admin_id')) {
            return redirect()->route('setup.show', ['step' => 'project']);
        }

        return view('setup.show', [
            'step' => $step,
            'steps' => self::STEPS,
            'checks' => $step === 'requirements' ? $this->requirements() : [],
            'projectName' => old('app_name', config('app.name') === 'Laravel' ? 'Pilot' : config('app.name')),
            'projectUrl' => old('app_url', config('app.url')),
        ]);
    }

    public function database(Request $request): RedirectResponse
    {
        abort_if($this->state->installed(), 404);

        $credentials = $request->validate([
            'connection' => ['required', Rule::in(['mysql', 'pgsql', 'sqlite'])],
            'host' => ['nullable', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'between:1,65535'],
            'database' => ['required', 'string', 'max:1024'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:1024'],
        ]);

        if ($credentials['connection'] === 'sqlite') {
            $path = $credentials['database'];
            $path = str_starts_with($path, DIRECTORY_SEPARATOR) ? $path : base_path($path);
            File::ensureDirectoryExists(dirname($path));

            if (! File::exists($path)) {
                File::put($path, '');
            }

            $credentials['database'] = $path;
        }

        try {
            $this->installer->configureDatabase($credentials);
            $this->installer->prepareDatabase(true);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput($request->except('password'))->withErrors([
                'database' => $exception->getMessage(),
            ]);
        }

        $request->session()->put('pilot_setup.database_ready', true);

        $rememberedAdmin = User::query()
            ->whereKey($request->session()->get('pilot_setup.admin_id'))
            ->where('email', $request->session()->get('pilot_setup.admin_email'))
            ->exists();

        if (! $rememberedAdmin) {
            $request->session()->forget([
                'pilot_setup.admin_id',
                'pilot_setup.admin_email',
                'pilot_setup.project',
            ]);
        }

        return redirect()->route('setup.show', ['step' => 'account']);
    }

    public function account(Request $request): RedirectResponse
    {
        abort_if($this->state->installed(), 404);
        abort_unless($request->session()->get('pilot_setup.database_ready'), 409);

        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $admin = $this->installer->createAdministrator($attributes);
        $request->session()->put('pilot_setup.admin_id', $admin->getKey());
        $request->session()->put('pilot_setup.admin_email', $admin->email);

        return redirect()->route('setup.show', ['step' => 'project']);
    }

    public function project(Request $request): RedirectResponse
    {
        abort_if($this->state->installed(), 404);
        abort_unless($request->session()->get('pilot_setup.admin_id'), 409);

        $attributes = $request->validate([
            'app_name' => ['required', 'string', 'max:80'],
            'app_url' => ['required', 'url:http,https', 'max:255'],
            'locale' => ['required', Rule::in(['en', 'es', 'fr', 'de'])],
        ]);

        $this->installer->configureProject([
            'APP_NAME' => $attributes['app_name'],
            'APP_URL' => rtrim($attributes['app_url'], '/'),
            'APP_LOCALE' => $attributes['locale'],
            'APP_FALLBACK_LOCALE' => $attributes['locale'],
        ]);

        $request->session()->put('pilot_setup.project', $attributes);

        return redirect()->route('setup.show', ['step' => 'developer']);
    }

    public function finish(Request $request): RedirectResponse
    {
        abort_if($this->state->installed(), 404);
        abort_unless($request->session()->get('pilot_setup.project'), 409);

        $admin = User::findOrFail($request->session()->get('pilot_setup.admin_id'));
        $this->installer->finish($admin);
        Auth::login($admin);
        $request->session()->forget('pilot_setup');
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard')->with('status', 'Pilot is ready. Welcome aboard!');
    }

    /** @return array<int, array{label:string,passed:bool,detail:string}> */
    private function requirements(): array
    {
        $checks = [
            ['PHP 8.4.1 or newer', PHP_VERSION_ID >= 80401, PHP_VERSION],
            ['PDO extension', extension_loaded('pdo'), extension_loaded('pdo') ? 'Available' : 'Missing'],
            ['Mbstring extension', extension_loaded('mbstring'), extension_loaded('mbstring') ? 'Available' : 'Missing'],
            ['OpenSSL extension', extension_loaded('openssl'), extension_loaded('openssl') ? 'Available' : 'Missing'],
            ['Writable storage directory', is_writable(storage_path()), storage_path()],
            ['Writable environment file', is_writable(app()->environmentFilePath()) || is_writable(base_path()), app()->environmentFilePath()],
        ];

        return array_map(fn (array $check): array => [
            'label' => $check[0],
            'passed' => $check[1],
            'detail' => $check[2],
        ], $checks);
    }
}
