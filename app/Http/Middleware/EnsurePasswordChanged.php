<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user && $user->password_changed_at === null && ! $request->routeIs('password.*', 'logout')) {
            return redirect()->route('password.edit');
        }

        if ($user && $user->password_changed_at !== null && $request->routeIs('password.edit')) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
