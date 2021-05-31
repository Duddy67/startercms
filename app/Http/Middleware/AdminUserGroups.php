<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminUserGroups
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
	$routeName = $request->route()->getName();

        $create = ['admin.usergroups.index', 'admin.usergroups.create', 'admin.usergroups.store'];
        $update = ['admin.usergroups.update', 'admin.usergroups.edit'];
        $delete = ['admin.usergroups.destroy', 'admin.usergroups.massDestroy'];

	if (in_array($routeName, $create) && !auth()->user()->isAllowedTo('create-user-group')) {
	    return redirect()->route('admin')->with('error', 'You are not authorized to access this resource.');
	}

	if (in_array($routeName, $update) && !auth()->user()->isAllowedTo('update-user-group')) {
	    return redirect()->route('admin.users.index')->with('error', 'You are not authorized to edit user groups.');
	}

	if (in_array($routeName, $delete) && !auth()->user()->isAllowedTo('delete-user-group')) {
	    return redirect()->route('admin.users.index')->with('error', 'You are not authorized to delete user groups.');
	}

        return $next($request);
    }
}
