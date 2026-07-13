<?php

namespace App\Http\Middleware;

use App\Enums\SessionStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureExamSessionActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $session = $request->route('examSession');

        if (! $session || $session->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized.');
        }

        if ($session->status !== SessionStatus::IN_PROGRESS) {
            return to_route('dashboard')
                ->with('error', 'Sesi ujian sudah tidak aktif.');
        }

        return $next($request);
    }
}
