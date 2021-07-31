<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Traits\Admin\RolesPermissions;

class AdminUsersPermissions
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
	$routeName = $request->route()->getName();
        $access = ['admin.users.permissions.index', 'admin.users.permissions.build', 'admin.users.permissions.rebuild'];

	if (in_array($routeName, $access) && $this->getUserRoleType(auth()->user()) != 'super-admin') {
	    return redirect()->route('admin')->with('error', __('messages.generic.access_not_auth'));
	}

        return $next($request);
    }
}
