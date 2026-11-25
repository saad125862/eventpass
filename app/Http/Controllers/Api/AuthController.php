<?php

namespace App\Http\Controllers\Api;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:60'],
        ]);

        if (! Auth::validate(['email' => $credentials['email'], 'password' => $credentials['password']])) {
            return response()->json(['message' => 'These credentials do not match our records.'], 422);
        }

        /** @var User $user */
        $user = User::query()->where('email', $credentials['email'])->firstOrFail();

        return $this->tokenResponse($user, $credentials['device_name'] ?? 'mobile');
    }

    /** One-tap demo sign-in. Same rules as the website: flag-gated, attendee/organizer only. */
    public function demo(Request $request, string $role): JsonResponse
    {
        abort_unless(config('eventpass.demo_login'), 404);

        $email = match ($role) {
            Role::Attendee->value => 'attendee@eventpass.test',
            Role::Organizer->value => 'organizer@eventpass.test',
            default => null,
        };

        $user = $email ? User::query()->where('email', $email)->first() : null;
        abort_unless($user !== null, 404);

        return $this->tokenResponse($user, 'mobile-demo');
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json(['user' => $this->present($user)]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()->delete();

        return response()->json(['message' => 'Signed out.']);
    }

    private function tokenResponse(User $user, string $device): JsonResponse
    {
        return response()->json([
            'token' => $user->createToken($device)->plainTextToken,
            'user' => $this->present($user),
        ]);
    }

    /** @return array{id: int, name: string, email: string, role: string, can_organize: bool} */
    private function present(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
            'can_organize' => $user->canOrganize(),
        ];
    }
}
