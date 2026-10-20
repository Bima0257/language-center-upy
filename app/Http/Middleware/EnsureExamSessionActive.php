<?php

namespace App\Http\Middleware;

use App\Enums\StatusSesi;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureExamSessionActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $sesiUjian = $request->route('sesiUjian');

        if (! $sesiUjian || $sesiUjian->pengguna_id !== $request->user()->id) {
            abort(403, 'Unauthorized.');
        }

        if ($sesiUjian->status !== StatusSesi::IN_PROGRESS) {
            return to_route('dasbor')
                ->with('error', 'Sesi ujian sudah tidak aktif.');
        }

        return $next($request);
    }
}
