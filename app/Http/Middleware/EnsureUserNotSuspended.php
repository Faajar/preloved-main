<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserNotSuspended
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->isSuspended()) {
            $reason = $user->suspension_reason ? ' (Alasan: '.$user->suspension_reason.')' : '';

            abort(403, 'Akun Anda sedang ditangguhkan'.$reason);
        }

        return $next($request);
    }
}
