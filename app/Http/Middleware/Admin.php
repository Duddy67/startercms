<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class Admin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (in_array(User::getRoleType(), ['super-admin', 'admin', 'manager', 'assistant'])) {
	    return $next($request);
	}

	return redirect()->route('profile');
    }
}
