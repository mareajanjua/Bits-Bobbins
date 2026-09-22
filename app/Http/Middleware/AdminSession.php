<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminSession
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->session()->has('admin_id')) {
            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
