<?php

namespace App\Http\Middleware;

use App\Services\AuthService;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureWebAdminToken
{
    public function __construct(private AuthService $authService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken && $token->name === AuthService::WEB_TOKEN_NAME) {
            if (!$this->authService->isWebAdmin($user)) {
                $token->delete();

                return response()->json([
                    'message' => 'Administrator access required.',
                ], 403);
            }
        }

        return $next($request);
    }
}
