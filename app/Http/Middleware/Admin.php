<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Traits\Admin\RolesPermissions;

class Admin
{
    use RolesPermissions;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (in_array($this->getUserRoleType(), ['super-admin', 'admin', 'manager', 'assistant'])) {
	    return $next($request);
	}

	return redirect()->route('profile');
    }
}
