<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureExamReady
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isVerified()) {
            return to_route('onboarding.verify-identity')
                ->with('error', 'Verifikasi identitas diperlukan sebelum mengikuti ujian.');
        }

        return $next($request);
    }
}
