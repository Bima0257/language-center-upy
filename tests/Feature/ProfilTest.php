<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfilTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $pengguna = Pengguna::factory()->create();

        $response = $this
            ->actingAs($pengguna)
            ->get('/profil');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $pengguna = Pengguna::factory()->create();

        $response = $this
            ->actingAs($pengguna)
            ->patch('/profil', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profil');

        $pengguna->refresh();

        $this->assertSame('Test User', $pengguna->name);
        $this->assertSame('test@example.com', $pengguna->email);
        $this->assertNull($pengguna->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $pengguna = Pengguna::factory()->create();

        $response = $this
            ->actingAs($pengguna)
            ->patch('/profil', [
                'name' => 'Test User',
                'email' => $pengguna->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profil');

        $this->assertNotNull($pengguna->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $pengguna = Pengguna::factory()->create();

        $response = $this
            ->actingAs($pengguna)
            ->delete('/profil', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($pengguna->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $pengguna = Pengguna::factory()->create();

        $response = $this
            ->actingAs($pengguna)
            ->from('/profil')
            ->delete('/profil', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/profil');

        $this->assertNotNull($pengguna->fresh());
    }
}
