<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminUsersRoles
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

        $create = ['admin.users.roles.index', 'admin.users.roles.create', 'admin.users.roles.store'];
        $update = ['admin.users.roles.update', 'admin.users.roles.edit'];
        $delete = ['admin.users.roles.destroy', 'admin.users.roles.massDestroy'];

	if (in_array($routeName, $create) && !auth()->user()->isAllowedTo('create-role')) {
	    return redirect()->route('admin')->with('error', 'You are not authorized to access this resource.');
	}

	if (in_array($routeName, $update) && !auth()->user()->isAllowedTo('update-role')) {
	    return redirect()->route('admin.users.roles.index')->with('error', 'You are not authorized to edit roles.');
	}

	if (in_array($routeName, $delete) && !auth()->user()->isAllowedTo('delete-role')) {
	    return redirect()->route('admin.users.roles.index')->with('error', 'You are not authorized to delete roles.');
	}

        $create = ['admin.users.permissions.index', 'admin.users.permissions.create', 'admin.users.permissions.store'];
        $update = ['admin.users.permissions.update', 'admin.users.permissions.edit'];
        $delete = ['admin.users.permissions.destroy', 'admin.users.permissions.massDestroy'];

	if (in_array($routeName, $create) && !auth()->user()->isAllowedTo('create-permission')) {
	    return redirect()->route('admin')->with('error', 'You are not authorized to access this resource.');
	}

	if (in_array($routeName, $update) && !auth()->user()->isAllowedTo('update-permission')) {
	    return redirect()->route('admin.users.permissions.index')->with('error', 'You are not authorized to edit permissions.');
	}

	if (in_array($routeName, $delete) && !auth()->user()->isAllowedTo('delete-permission')) {
	    return redirect()->route('admin.users.permissions.index')->with('error', 'You are not authorized to delete permissions.');
	}

        return $next($request);
    }
}
