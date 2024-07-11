<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlockIpRange
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $blockedIpRange = '153.92';

        if (Str::startsWith($request->ip(), $blockedIpRange)) {
            return response('Access denied', 403);
        }

        return $next($request);
    }
}
