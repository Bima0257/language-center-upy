<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class Turnstile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (app()->environment('local', 'testing')) {
            return;
        }

        if (empty($value)) {
            $fail('Verifikasi keamanan diperlukan.');

            return;
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => config('services.turnstile.secret_key'),
                    'response' => $value,
                    'remoteip' => request()->ip(),
                ]);

            $body = $response->json();
        } catch (\Throwable $e) {
            $fail('Verifikasi keamanan tidak dapat diproses. Silakan coba lagi.');

            return;
        }

        if (! ($body['success'] ?? false)) {
            $fail('Verifikasi keamanan gagal. Silakan coba lagi.');
        }
    }
}
