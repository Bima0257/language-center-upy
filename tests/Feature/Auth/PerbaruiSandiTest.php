<?php

namespace Tests\Feature\Auth;

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PerbaruiSandiTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_updated(): void
    {
        $pengguna = Pengguna::factory()->create();

        $response = $this
            ->actingAs($pengguna)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertTrue(Hash::check('NewPassword123!', $pengguna->refresh()->password));
    }

    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $pengguna = Pengguna::factory()->create();

        $response = $this
            ->actingAs($pengguna)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'wrong-password',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        $response
            ->assertSessionHasErrors('current_password')
            ->assertRedirect('/profile');
    }
}
