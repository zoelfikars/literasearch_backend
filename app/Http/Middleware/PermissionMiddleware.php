<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;

class PermissionMiddleware
{
    use ApiResponse;
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user || !in_array($user->role, $roles)) {
            return $this->setResponse('Anda tidak memiliki hak akses', 403);
        }
        return $next($request);
    }
}
