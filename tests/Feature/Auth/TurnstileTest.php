<?php

namespace Tests\Feature\Auth;

use App\Models\Pengguna;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TurnstileTest extends TestCase
{
    use RefreshDatabase;

    private function aktifkanVerifikasiTurnstile(): void
    {
        config([
            'services.turnstile.verify' => true,
            'services.turnstile.secret_key' => 'test-secret',
            'services.turnstile.allowed_hostnames' => [],
        ]);
    }

    public function test_login_ditolak_tanpa_token_turnstile(): void
    {
        $this->aktifkanVerifikasiTurnstile();

        $pengguna = Pengguna::factory()->create();

        $response = $this->post('/login', [
            'email' => $pengguna->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('cf-turnstile-response');
        $this->assertGuest();
    }

    public function test_login_berhasil_dengan_token_turnstile_valid(): void
    {
        $this->aktifkanVerifikasiTurnstile();

        Http::fake([
            'challenges.cloudflare.com/*' => Http::response([
                'success' => true,
                'action' => 'login',
                'hostname' => 'localhost',
            ]),
        ]);

        $pengguna = Pengguna::factory()->create();

        $response = $this->post('/login', [
            'email' => $pengguna->email,
            'password' => 'password',
            'cf-turnstile-response' => 'dummy-token',
        ]);

        $response->assertRedirect(route('dasbor', absolute: false));
        $this->assertAuthenticated();
    }

    public function test_login_ditolak_saat_siteverify_mengembalikan_gagal(): void
    {
        $this->aktifkanVerifikasiTurnstile();

        Http::fake([
            'challenges.cloudflare.com/*' => Http::response([
                'success' => false,
                'error-codes' => ['invalid-input-response'],
            ]),
        ]);

        $pengguna = Pengguna::factory()->create();

        $response = $this->post('/login', [
            'email' => $pengguna->email,
            'password' => 'password',
            'cf-turnstile-response' => 'dummy-token',
        ]);

        $response->assertSessionHasErrors('cf-turnstile-response');
        $this->assertGuest();
    }

    public function test_login_ditolak_saat_action_turnstile_tidak_sesuai(): void
    {
        $this->aktifkanVerifikasiTurnstile();

        Http::fake([
            'challenges.cloudflare.com/*' => Http::response([
                'success' => true,
                'action' => 'register',
                'hostname' => 'localhost',
            ]),
        ]);

        $pengguna = Pengguna::factory()->create();

        $response = $this->post('/login', [
            'email' => $pengguna->email,
            'password' => 'password',
            'cf-turnstile-response' => 'dummy-token',
        ]);

        $response->assertSessionHasErrors('cf-turnstile-response');
        $this->assertGuest();
    }

    public function test_login_ditolak_saat_hostname_tidak_diizinkan(): void
    {
        $this->aktifkanVerifikasiTurnstile();

        config(['services.turnstile.allowed_hostnames' => ['app.example.com']]);

        Http::fake([
            'challenges.cloudflare.com/*' => Http::response([
                'success' => true,
                'action' => 'login',
                'hostname' => 'localhost',
            ]),
        ]);

        $pengguna = Pengguna::factory()->create();

        $response = $this->post('/login', [
            'email' => $pengguna->email,
            'password' => 'password',
            'cf-turnstile-response' => 'dummy-token',
        ]);

        $response->assertSessionHasErrors('cf-turnstile-response');
        $this->assertGuest();
    }

    public function test_login_ditolak_saat_siteverify_tidak_dapat_dihubungi(): void
    {
        $this->aktifkanVerifikasiTurnstile();

        Http::fake(function (): void {
            throw new ConnectionException('Connection timed out');
        });

        $pengguna = Pengguna::factory()->create();

        $response = $this->post('/login', [
            'email' => $pengguna->email,
            'password' => 'password',
            'cf-turnstile-response' => 'dummy-token',
        ]);

        $response->assertSessionHasErrors('cf-turnstile-response');
        $this->assertGuest();
    }

    public function test_pendaftaran_berhasil_dengan_action_turnstile_register(): void
    {
        $this->aktifkanVerifikasiTurnstile();

        Http::fake([
            'challenges.cloudflare.com/*' => Http::response([
                'success' => true,
                'action' => 'register',
                'hostname' => 'localhost',
            ]),
        ]);

        $this->seed(RoleSeeder::class);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'cf-turnstile-response' => 'dummy-token',
        ]);

        $response->assertRedirect(route('verification.notice', absolute: false));
        $this->assertAuthenticated();
    }
}
