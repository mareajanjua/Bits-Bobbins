<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EmployeeSession
{
    public function handle(Request $request, Closure $next)
    {
        if (! session('employee_id')) {
            return redirect()->route('customer.login')->with('status', 'Please login as employee to continue.');
        }

        return $next($request);
    }
}
