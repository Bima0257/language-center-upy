<?php

namespace App\Http\Middleware;

use App\Models\Pengguna;
use App\Modules\Identity\Contracts\IdentityContract;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceRoutePermission
{
    public function __construct(
        private IdentityContract $identity,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $pengguna = $request->user();

        if (! $pengguna instanceof Pengguna) {
            abort(403);
        }

        if ($this->identity->userHasRole($pengguna, 'superadmin')) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        abort_if(blank($routeName), 403);
        abort_unless($this->identity->userHasPermission($pengguna, $routeName), 403);

        return $next($request);
    }
}
