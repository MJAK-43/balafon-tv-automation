<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domains\Audit\Services\AuditService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuditService $audit,
    ) {
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        $token = $user->createToken('balafon-spa')->plainTextToken;

        $this->audit->log('auth.login', 'user', (string) $user->id, ['email' => $user->email], $user->id, 'user', $request->ip(), $request->userAgent());

        return response()->json([
            'token' => $token,
            'user' => $user->load('roles'),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $request->user()?->currentAccessToken()?->delete();

        if ($user) {
            $this->audit->log('auth.logout', 'user', (string) $user->id, [], $user->id, 'user', $request->ip(), $request->userAgent());
        }

        return response()->json(['success' => true]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user()?->load('roles'));
    }
}
