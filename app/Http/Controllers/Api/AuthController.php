<?php

namespace App\Http\Controllers\Api;

use App\Services\AuthService;
use App\Services\UserService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private UserService $userService,
        private AuthService $authService,
    ) {
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = $this->userService->create($validated);

        $token = $user->createToken(AuthService::MOBILE_TOKEN_NAME)->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'client' => 'nullable|string|in:web,mobile',
        ]);

        if (!auth()->attempt([
            'email' => $validated['email'],
            'password' => $validated['password'],
        ])) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        $user = $request->user();
        $user->load('workerType');
        $client = $validated['client'] ?? 'mobile';

        if ($client === 'web') {
            if (!$this->authService->isWebAdmin($user)) {
                auth()->logout();

                return response()->json([
                    'message' => 'Only administrators can access the web dashboard.',
                ], 403);
            }

            $token = $this->authService->issueWebToken($user);

            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user,
                'message' => 'Signed in. Any other active web session has been ended.',
            ]);
        }

        $token = $this->authService->issueMobileToken($user);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
}
