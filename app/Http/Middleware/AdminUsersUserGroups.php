<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminUsersUserGroups
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

        $create = ['admin.users.usergroups.index', 'admin.users.usergroups.create', 'admin.users.usergroups.store'];
        $update = ['admin.users.usergroups.update', 'admin.users.usergroups.edit'];
        $delete = ['admin.users.usergroups.destroy', 'admin.users.usergroups.massDestroy'];

	if (in_array($routeName, $create) && !auth()->user()->isAllowedTo('create-user-group')) {
	    return redirect()->route('admin')->with('error', 'You are not authorized to access this resource.');
	}

	if (in_array($routeName, $update) && !auth()->user()->isAllowedTo('update-user-group')) {
	    return redirect()->route('admin.users.usergroups.index')->with('error', 'You are not authorized to edit user groups.');
	}

	if (in_array($routeName, $delete) && !auth()->user()->isAllowedTo('delete-user-group')) {
	    return redirect()->route('admin.users.usergroups.index')->with('error', 'You are not authorized to delete user groups.');
	}

        return $next($request);
    }
}
