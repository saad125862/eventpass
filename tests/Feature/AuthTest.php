<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsEvents;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use BuildsEvents, RefreshDatabase;

    public function test_pages_render_for_guests(): void
    {
        $this->get('/')->assertOk()->assertSee('Every great night');
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
    }

    public function test_user_can_register_as_attendee_or_organizer(): void
    {
        $this->post('/register', [
            'name' => 'Ada Lovelace', 'email' => 'ada@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123', 'role' => 'organizer',
        ])->assertRedirect(route('organizer.dashboard'));

        $this->assertAuthenticated();
        $this->assertSame(Role::Organizer, User::where('email', 'ada@example.com')->first()->role);
    }

    public function test_admin_role_cannot_be_self_assigned(): void
    {
        $this->post('/register', [
            'name' => 'Mallory', 'email' => 'm@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123', 'role' => 'admin',
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'm@example.com']);
    }

    public function test_registration_validates_input(): void
    {
        $this->user(attrs: ['email' => 'taken@example.com']);

        $this->post('/register', ['name' => '', 'email' => 'taken@example.com', 'password' => 'short', 'role' => 'attendee'])
            ->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_login_and_logout(): void
    {
        $user = $this->user(attrs: ['email' => 'lee@example.com', 'password' => 'secret-pass-1']);

        $this->post('/login', ['email' => 'lee@example.com', 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/login', ['email' => 'lee@example.com', 'password' => 'secret-pass-1'])->assertRedirect(route('events.index'));
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_guests_are_sent_to_login_for_protected_pages(): void
    {
        foreach (['/tickets', '/organizer', '/admin'] as $path) {
            $this->get($path)->assertRedirect(route('login'));
        }
    }
}
