<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Support\Facades\Log;

class Authenticate extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        Log::info('Authenticate Middleware: redirectTo method called.');
        if (!$request->expectsJson() && !$request->is('api/*')) {
            Log::info('API Request: Redirecting to not null. Request wants JSON: ' . $request->expectsJson() . ' Is API: ' . $request->is('api/*'));
            return route('login');
        }
        Log::info('API Request: Redirecting to null. Request wants JSON: ' . $request->expectsJson() . ' Is API: ' . $request->is('api/*'));
        return null;
    }
}
