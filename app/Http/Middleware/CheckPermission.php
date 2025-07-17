<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    use ApiResponse;
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        $user = $request->user();

        if (!$user) {
            return $this->setResponse('Anda belum melakukan login', null, 401);
        }

        $hasPermission = $user->roles()->whereHas('permissions', function ($query) use ($permissions) {
            $query->whereIn('name', $permissions);
        })->exists();

        if (!$hasPermission) {
            return $this->setResponse('Anda tidak memiliki hak akses', null, 403);
        }

        return $next($request);
    }
}
