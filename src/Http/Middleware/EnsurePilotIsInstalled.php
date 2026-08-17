<?php

namespace Pilot\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Pilot\Core\Support\Installation\InstallationState;
use Symfony\Component\HttpFoundation\Response;

class EnsurePilotIsInstalled
{
    public function __construct(private readonly InstallationState $state) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->state->installed() || $request->is('setup', 'setup/*', 'up')) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Pilot has not been configured. Complete setup in the browser first.',
                'setup_url' => route('setup.show'),
            ], 503);
        }

        return redirect()->route('setup.show');
    }
}
