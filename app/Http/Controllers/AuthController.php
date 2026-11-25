<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        $request->session()->regenerate();

        /** @var User $user */
        $user = $request->user();

        return redirect()->intended($this->homeFor($user))
            ->with('status', 'Welcome back, '.explode(' ', $user->name)[0].'!');
    }

    /**
     * One-click sign-in as a seeded demo user. Only for demo deployments (DEMO_LOGIN=true),
     * and only for the attendee and organizer demo accounts, never admin.
     */
    public function demo(Request $request, string $role): RedirectResponse
    {
        abort_unless(config('eventpass.demo_login'), 404);

        $email = match ($role) {
            Role::Attendee->value => 'attendee@eventpass.test',
            Role::Organizer->value => 'organizer@eventpass.test',
            default => null,
        };

        $user = $email ? User::query()->where('email', $email)->first() : null;
        abort_unless($user !== null, 404);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->to($this->homeFor($user))
            ->with('status', 'Signed in to the demo as '.$user->name.'.');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            // Admin is never self-assignable.
            'role' => ['required', Rule::in([Role::Attendee->value, Role::Organizer->value])],
        ]);

        $user = User::create($data);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect($this->homeFor($user))
            ->with('status', 'Your account is ready. Welcome to EventPass!');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'You have been signed out.');
    }

    private function homeFor(User $user): string
    {
        return $user->canOrganize() ? route('organizer.dashboard') : route('events.index');
    }
}
