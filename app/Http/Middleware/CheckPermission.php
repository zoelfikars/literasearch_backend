<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    use ApiResponse;
    public function handle(Request $request, Closure $next, array|string $permissions): Response
    {
        $user = auth()->user();

        $permissions = is_array($permissions) ? $permissions : explode(',', $permissions);

        $hasPermission = !$user ? false : $user->roles()->whereHas('permissions', function ($query) use ($permissions) {
            if (is_array($permissions)) {
                $query->whereIn('name', $permissions);
            } else {
                $query->where('name', $permissions);
            }
        })->exists();



        if (!$hasPermission) {
            $this->setResponse('error', 'Anda tidak memiliki hak akses', null, 403);
        }

        return $next($request);
    }
}
