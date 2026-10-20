<?php

namespace Tests\Feature\Auth;

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutentikasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $pengguna = Pengguna::factory()->create();

        $response = $this->post('/login', [
            'email' => $pengguna->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dasbor', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $pengguna = Pengguna::factory()->create();

        $this->post('/login', [
            'email' => $pengguna->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $pengguna = Pengguna::factory()->create();

        $response = $this->actingAs($pengguna)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_users_can_authenticate_with_remember_me(): void
    {
        $pengguna = Pengguna::factory()->create();

        $response = $this->post('/login', [
            'email' => $pengguna->email,
            'password' => 'password',
            'remember' => true,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dasbor', absolute: false));

        $this->assertNotNull($pengguna->fresh()->remember_token);
        $response->assertCookie(auth()->guard()->getRecallerName());
    }

    public function test_users_can_authenticate_without_remember_me(): void
    {
        $pengguna = Pengguna::factory()->create();

        $response = $this->post('/login', [
            'email' => $pengguna->email,
            'password' => 'password',
            'remember' => false,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dasbor', absolute: false));

        $this->assertNull($pengguna->fresh()->remember_token);
        $response->assertCookieMissing(auth()->guard()->getRecallerName());
    }

    public function test_recaller_cookie_restores_session(): void
    {
        $pengguna = Pengguna::factory()->create();

        $response = $this->post('/login', [
            'email' => $pengguna->email,
            'password' => 'password',
            'remember' => true,
        ]);

        $recallerName = auth()->guard()->getRecallerName();
        $recallerValue = collect($response->headers->getCookies())
            ->first(fn ($cookie) => $cookie->getName() === $recallerName)
            ->getValue();

        $this->app['session']->flush();

        $this->call('GET', '/dasbor', [], [$recallerName => $recallerValue]);

        $this->assertAuthenticated();
    }
}
