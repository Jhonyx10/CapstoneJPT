<?php

namespace App\Services;

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    public const WEB_TOKEN_NAME = 'web_admin_token';

    public const MOBILE_TOKEN_NAME = 'auth_token';

    public function isWebAdmin(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Only one active web dashboard session at a time.
     * Logging in revokes every existing web admin token.
     */
    public function issueWebToken(User $user): string
    {
        PersonalAccessToken::where('name', self::WEB_TOKEN_NAME)->delete();

        return $user->createToken(self::WEB_TOKEN_NAME)->plainTextToken;
    }

    public function issueMobileToken(User $user): string
    {
        return $user->createToken(self::MOBILE_TOKEN_NAME)->plainTextToken;
    }
}
