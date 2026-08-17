<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => 'Set up Pilot CMS'])
    </head>
    <body class="min-h-screen bg-app text-primary antialiased">
        @php
            $currentIndex = array_search($step, $steps, true);
            $labels = ['Welcome', 'System check', 'Database', 'Your account', 'Project', 'Developer setup'];
            $previous = $currentIndex > 0 ? $steps[$currentIndex - 1] : null;
        @endphp

        <div class="min-h-screen lg:grid lg:grid-cols-[320px_minmax(0,1fr)]">
            <aside class="border-b border-subtle bg-sunken px-6 py-6 lg:min-h-screen lg:border-r lg:border-b-0 lg:px-8 lg:py-10">
                <div class="flex items-center gap-3">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-accent shadow-sm">
                        <img src="{{ asset('img/logo.svg') }}" alt="" class="size-6" />
                    </span>
                    <div>
                        <div class="font-semibold tracking-tight">Pilot CMS</div>
                        <div class="text-xs text-tertiary">First-flight setup</div>
                    </div>
                </div>

                <ol class="mt-8 hidden space-y-1 lg:block" aria-label="Setup progress">
                    @foreach($labels as $index => $label)
                        <li class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm {{ $index === $currentIndex ? 'bg-card font-semibold text-primary shadow-xs' : ($index < $currentIndex ? 'text-secondary' : 'text-tertiary') }}">
                            <span class="flex size-6 shrink-0 items-center justify-center rounded-full border text-xs {{ $index < $currentIndex ? 'border-success bg-success-subtle text-success' : ($index === $currentIndex ? 'border-accent bg-accent text-on-accent' : 'border-subtle') }}">
                                @if($index < $currentIndex)
                                    ✓
                                @else
                                    {{ $index + 1 }}
                                @endif
                            </span>
                            {{ $label }}
                        </li>
                    @endforeach
                </ol>

                <div class="mt-5 lg:hidden">
                    <div class="mb-2 flex justify-between text-xs text-tertiary">
                        <span>Step {{ $currentIndex + 1 }} of {{ count($steps) }}</span>
                        <span>{{ $labels[$currentIndex] }}</span>
                    </div>
                    <div class="h-1.5 overflow-hidden rounded-full bg-gray-200">
                        <div class="h-full rounded-full bg-accent" style="width: {{ (($currentIndex + 1) / count($steps)) * 100 }}%"></div>
                    </div>
                </div>

                <p class="mt-10 hidden text-xs leading-5 text-tertiary lg:block">Configuration stays on this server. Database credentials are written directly to your local environment file.</p>
            </aside>

            <main class="flex min-h-[calc(100vh-130px)] items-center justify-center px-5 py-10 sm:px-10 lg:min-h-screen lg:px-16">
                <div class="w-full max-w-2xl">
                    @if($errors->any())
                        <div class="mb-6 rounded-xl border border-danger-border bg-danger-subtle px-4 py-3 text-sm text-danger" role="alert">
                            <p class="font-semibold">We couldn’t continue yet.</p>
                            <p class="mt-1">{{ $errors->first() }}</p>
                        </div>
                    @endif

                    @if($step === 'welcome')
                        <div class="inline-flex items-center gap-2 rounded-full border border-brand-border bg-brand-subtle px-3 py-1 text-xs font-semibold text-brand-text">
                            <span class="size-1.5 rounded-full bg-brand"></span>
                            Ready for takeoff
                        </div>
                        <h1 class="mt-5 text-4xl font-semibold tracking-[-0.035em] text-primary sm:text-5xl">Let’s set up your Pilot workspace.</h1>
                        <p class="mt-5 max-w-xl text-lg leading-8 text-secondary">This guided setup will connect your database, create the first administrator, and give you the exact commands to continue in your IDE.</p>
                        <div class="mt-8 grid gap-3 sm:grid-cols-3">
                            @foreach([['01', 'Check your server'], ['02', 'Connect your data'], ['03', 'Start building']] as [$number, $label])
                                <div class="rounded-xl border border-subtle bg-card p-4 shadow-xs">
                                    <div class="font-mono text-xs text-accent-text">{{ $number }}</div>
                                    <div class="mt-2 text-sm font-medium">{{ $label }}</div>
                                </div>
                            @endforeach
                        </div>
                        <a href="{{ route('setup.show', ['step' => 'requirements']) }}" class="cms-btn cms-btn-primary mt-8 inline-flex">Begin setup <span aria-hidden="true">→</span></a>

                    @elseif($step === 'requirements')
                        <p class="text-sm font-semibold text-accent-text">System check</p>
                        <h1 class="mt-2 text-3xl font-semibold tracking-tight">Everything Pilot needs to fly</h1>
                        <p class="mt-3 text-secondary">Resolve any failed checks before connecting your database.</p>
                        <div class="mt-7 overflow-hidden rounded-xl border border-subtle bg-card shadow-xs">
                            @foreach($checks as $check)
                                <div class="flex items-center gap-3 border-b border-subtle px-4 py-3.5 last:border-0">
                                    <span class="flex size-7 items-center justify-center rounded-full {{ $check['passed'] ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">{{ $check['passed'] ? '✓' : '!' }}</span>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-sm font-medium">{{ $check['label'] }}</div>
                                        <div class="truncate font-mono text-xs text-tertiary">{{ $check['detail'] }}</div>
                                    </div>
                                    <span class="text-xs font-medium {{ $check['passed'] ? 'text-success' : 'text-danger' }}">{{ $check['passed'] ? 'Ready' : 'Required' }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-7 flex items-center justify-between">
                            <a href="{{ route('setup.show') }}" class="cms-btn cms-btn-secondary">Back</a>
                            @if(collect($checks)->every(fn (array $check) => $check['passed']))
                                <a href="{{ route('setup.show', ['step' => 'database']) }}" class="cms-btn cms-btn-primary">Continue</a>
                            @else
                                <button type="button" class="cms-btn cms-btn-primary opacity-50" disabled>Continue</button>
                            @endif
                        </div>

                    @elseif($step === 'database')
                        <p class="text-sm font-semibold text-accent-text">Database</p>
                        <h1 class="mt-2 text-3xl font-semibold tracking-tight">Connect Pilot to your data</h1>
                        <p class="mt-3 text-secondary">Pilot will test the connection, run its migrations, and seed required reference data.</p>
                        <form method="POST" action="{{ route('setup.database') }}" class="mt-7 space-y-5" data-database-form>
                            @csrf
                            <label class="block">
                                <span class="mb-2 block text-sm font-medium">Database driver</span>
                                <select name="connection" class="h-control w-full rounded-lg border border-strong bg-card px-3 text-sm outline-none focus:border-focus focus:ring-2 focus:ring-accent-subtle">
                                    <option value="mysql">MySQL</option>
                                    <option value="pgsql">PostgreSQL</option>
                                    <option value="sqlite">SQLite</option>
                                </select>
                            </label>
                            <div data-network-database class="grid gap-5 sm:grid-cols-[1fr_140px] {{ old('connection', 'mysql') === 'sqlite' ? 'hidden' : '' }}">
                                <flux:input name="host" label="Host" value="{{ old('host', '127.0.0.1') }}" />
                                <flux:input name="port" label="Port" type="number" value="{{ old('port', '3306') }}" />
                            </div>
                            <flux:input name="database" label="Database name or SQLite path" value="{{ old('database', 'pilot') }}" required />
                            <div data-network-database class="grid gap-5 sm:grid-cols-2 {{ old('connection', 'mysql') === 'sqlite' ? 'hidden' : '' }}">
                                <flux:input name="username" label="Username" value="{{ old('username', 'root') }}" />
                                <flux:input name="password" label="Password" type="password" autocomplete="new-password" />
                            </div>
                            <div class="flex items-center justify-between pt-2">
                                <a href="{{ route('setup.show', ['step' => 'requirements']) }}" class="cms-btn cms-btn-secondary">Back</a>
                                <button class="cms-btn cms-btn-primary" type="submit">Test and continue</button>
                            </div>
                        </form>

                    @elseif($step === 'account')
                        <p class="text-sm font-semibold text-accent-text">Administrator</p>
                        <h1 class="mt-2 text-3xl font-semibold tracking-tight">Create your account</h1>
                        <p class="mt-3 text-secondary">This is Pilot’s first administrator. You can invite the rest of your team later.</p>
                        <form method="POST" action="{{ route('setup.account') }}" class="mt-7 space-y-5">
                            @csrf
                            <flux:input name="name" label="Full name" value="{{ old('name') }}" autocomplete="name" required autofocus />
                            <flux:input name="email" label="Email address" type="email" value="{{ old('email') }}" autocomplete="email" required />
                            <div class="grid gap-5 sm:grid-cols-2">
                                <flux:input name="password" label="Password" type="password" autocomplete="new-password" required />
                                <flux:input name="password_confirmation" label="Confirm password" type="password" autocomplete="new-password" required />
                            </div>
                            <div class="flex items-center justify-between pt-2">
                                <a href="{{ route('setup.show', ['step' => 'database']) }}" class="cms-btn cms-btn-secondary">Back</a>
                                <button class="cms-btn cms-btn-primary" type="submit">Create administrator</button>
                            </div>
                        </form>

                    @elseif($step === 'project')
                        <p class="text-sm font-semibold text-accent-text">Project</p>
                        <h1 class="mt-2 text-3xl font-semibold tracking-tight">Name your workspace</h1>
                        <p class="mt-3 text-secondary">These values become the application defaults and can be changed later.</p>
                        <form method="POST" action="{{ route('setup.project') }}" class="mt-7 space-y-5">
                            @csrf
                            <flux:input name="app_name" label="Workspace name" value="{{ $projectName }}" required autofocus />
                            <flux:input name="app_url" label="Pilot URL" type="url" value="{{ $projectUrl }}" required />
                            <label class="block">
                                <span class="mb-2 block text-sm font-medium">Default language</span>
                                <select name="locale" class="h-control w-full rounded-lg border border-strong bg-card px-3 text-sm outline-none focus:border-focus focus:ring-2 focus:ring-accent-subtle">
                                    @foreach(['en' => 'English', 'es' => 'Spanish', 'fr' => 'French', 'de' => 'German'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('locale', 'en') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <div class="flex items-center justify-between pt-2">
                                <a href="{{ route('setup.show', ['step' => 'account']) }}" class="cms-btn cms-btn-secondary">Back</a>
                                <button class="cms-btn cms-btn-primary" type="submit">Save and continue</button>
                            </div>
                        </form>

                    @elseif($step === 'developer')
                        <div class="flex size-12 items-center justify-center rounded-xl bg-success-subtle text-xl text-success">✓</div>
                        <h1 class="mt-5 text-3xl font-semibold tracking-tight">Pilot is configured</h1>
                        <p class="mt-3 text-secondary">Open this project in your IDE and use these commands to start the complete development stack.</p>

                        <div class="mt-7 space-y-4">
                            <div class="rounded-xl border border-subtle bg-gray-950 p-4 text-gray-100 shadow-sm">
                                <div class="mb-3 flex items-center justify-between text-xs text-gray-400"><span>Terminal</span><span>Run from {{ basename(base_path()) }}</span></div>
                                <pre class="overflow-x-auto font-mono text-sm leading-7"><code>composer run dev</code></pre>
                            </div>
                            <div class="rounded-xl border border-subtle bg-card p-5 shadow-xs">
                                <h2 class="font-semibold">Connect a Laravel frontend</h2>
                                <p class="mt-1 text-sm text-secondary">Inside the frontend application, install Pilot’s Laravel connector and generate its configuration.</p>
                                <pre class="mt-3 overflow-x-auto rounded-lg bg-sunken p-3 font-mono text-xs leading-6"><code>composer require pilot/laravel
php artisan vendor:publish --tag=pilot-config</code></pre>
                            </div>
                            <div class="rounded-xl border border-subtle bg-card p-5 shadow-xs">
                                <h2 class="font-semibold">Enable IDE guidance <span class="font-normal text-tertiary">(optional)</span></h2>
                                <p class="mt-1 text-sm text-secondary">Generate Laravel Boost instructions for your supported coding agent.</p>
                                <pre class="mt-3 overflow-x-auto rounded-lg bg-sunken p-3 font-mono text-xs"><code>php artisan boost:update</code></pre>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('setup.finish') }}" class="mt-7 flex items-center justify-between">
                            @csrf
                            <a href="{{ route('setup.show', ['step' => 'project']) }}" class="cms-btn cms-btn-secondary">Back</a>
                            <button class="cms-btn cms-btn-primary" type="submit">Finish and open Pilot</button>
                        </form>
                    @endif
                </div>
            </main>
        </div>

        <script>
            document.querySelectorAll('[data-database-form]').forEach((form) => {
                const driver = form.querySelector('[name="connection"]');
                const updateFields = () => form.querySelectorAll('[data-network-database]').forEach((fields) => {
                    fields.classList.toggle('hidden', driver.value === 'sqlite');
                });

                driver.addEventListener('change', updateFields);
                updateFields();
            });
        </script>
    </body>
</html>
