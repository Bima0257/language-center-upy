<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Turnstile implements ValidationRule
{
    /**
     * Dummy secret keys resmi Cloudflare tidak mengembalikan `action` maupun `hostname`,
     * sehingga pengecekan metadata hanya dijalankan untuk secret key non-test.
     *
     * @see https://developers.cloudflare.com/turnstile/troubleshooting/testing/
     */
    private const TEST_SECRET_KEYS = [
        '1x0000000000000000000000000000000AA',
        '2x0000000000000000000000000000000AA',
        '3x0000000000000000000000000000000AA',
    ];

    public function __construct(private readonly ?string $expectedAction = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! config('services.turnstile.verify')) {
            return;
        }

        $secretKey = (string) config('services.turnstile.secret_key');

        if ($secretKey === '') {
            Log::error('TURNSTILE_SECRET_KEY belum dikonfigurasi.');

            $fail('Verifikasi keamanan tidak dapat diproses. Silakan coba lagi.');

            return;
        }

        if (! is_string($value) || trim($value) === '') {
            $fail('Verifikasi keamanan diperlukan.');

            return;
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => $secretKey,
                    'response' => $value,
                    'remoteip' => request()->ip(),
                ]);

            $body = (array) $response->json();
        } catch (\Throwable $e) {
            Log::warning('Turnstile siteverify tidak dapat dihubungi.', ['exception' => $e]);

            $fail('Verifikasi keamanan tidak dapat diproses. Silakan coba lagi.');

            return;
        }

        if (! ($body['success'] ?? false)) {
            Log::warning('Turnstile menolak token.', [
                'error_codes' => $body['error-codes'] ?? [],
            ]);

            $fail('Verifikasi keamanan gagal. Silakan coba lagi.');

            return;
        }

        if (in_array($secretKey, self::TEST_SECRET_KEYS, true)) {
            return;
        }

        if ($this->expectedAction !== null && ($body['action'] ?? null) !== $this->expectedAction) {
            Log::warning('Turnstile action tidak sesuai.', [
                'expected' => $this->expectedAction,
                'actual' => $body['action'] ?? null,
            ]);

            $fail('Verifikasi keamanan gagal. Silakan coba lagi.');

            return;
        }

        $allowedHostnames = config('services.turnstile.allowed_hostnames', []);
        $hostname = $body['hostname'] ?? null;

        if ($allowedHostnames !== [] && ! in_array($hostname, $allowedHostnames, true)) {
            Log::warning('Turnstile hostname tidak diizinkan.', ['hostname' => $hostname]);

            $fail('Verifikasi keamanan gagal. Silakan coba lagi.');
        }
    }
}
