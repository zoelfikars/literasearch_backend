<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRevokedToken
{
    use ApiResponse;
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();

        if ($token && $token->revoked_at !== null) {
            return $this->setResponse('Token anda sudah tidak valid', null, 401);
        }

        return $next($request);
    }
}
