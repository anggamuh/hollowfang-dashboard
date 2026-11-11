<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHollowfangAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->discord_id) {
            abort(403, 'Hubungkan akun Discord terlebih dahulu.');
        }
        $allowed = config('hollowfang.owner_discord_ids', []);
        if (! in_array((string) $user->discord_id, $allowed, true)) {
            abort(403, 'Akun ini tidak memiliki akses Hollowfang.');
        }
        return $next($request);
    }
}
