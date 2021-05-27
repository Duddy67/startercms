<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class AdminRoles
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

        $create = ['admin.roles.index', 'admin.roles.create', 'admin.roles.store'];
        $update = ['admin.roles.update', 'admin.roles.edit'];
        $delete = ['admin.roles.destroy', 'admin.roles.massDestroy'];

	if (in_array($routeName, $create) && !auth()->user()->isAllowedTo('create-role')) {
	    return redirect()->route('admin')->with('error', 'You are not authorized to access this resource.');
	}

	if (in_array($routeName, $update) && !auth()->user()->isAllowedTo('update-role')) {
	    return redirect()->route('admin.roles.index')->with('error', 'You are not authorized to edit roles.');
	}

	if (in_array($routeName, $delete) && !auth()->user()->isAllowedTo('delete-role')) {
	    return redirect()->route('admin.roles.index')->with('error', 'You are not authorized to delete roles.');
	}

        $create = ['admin.permissions.index', 'admin.permissions.create', 'admin.permissions.store'];
        $update = ['admin.permissions.update', 'admin.permissions.edit'];
        $delete = ['admin.permissions.destroy', 'admin.permissions.massDestroy'];

	if (in_array($routeName, $create) && !auth()->user()->isAllowedTo('create-permission')) {
	    return redirect()->route('admin')->with('error', 'You are not authorized to access this resource.');
	}

	if (in_array($routeName, $update) && !auth()->user()->isAllowedTo('update-permission')) {
	    return redirect()->route('admin.permissions.index')->with('error', 'You are not authorized to edit permissions.');
	}

	if (in_array($routeName, $delete) && !auth()->user()->isAllowedTo('delete-permission')) {
	    return redirect()->route('admin.permissions.index')->with('error', 'You are not authorized to delete permissions.');
	}

        return $next($request);
    }
}
