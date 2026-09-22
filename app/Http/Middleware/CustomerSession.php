<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CustomerSession
{
    public function handle(Request $request, Closure $next)
    {
        if (! session('customer_id')) {
            session(['intended_customer_url' => $request->fullUrl()]);
            return redirect()->route('customer.login')->with('status', 'Please login or register to continue.');
        }

        return $next($request);
    }
}
