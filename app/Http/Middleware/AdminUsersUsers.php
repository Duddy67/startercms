<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminUsersUsers
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

        $create = ['admin.users.users.index', 'admin.users.users.create', 'admin.users.users.store'];
        $update = ['admin.users.users.update', 'admin.users.users.edit'];
        $delete = ['admin.users.users.destroy', 'admin.users.users.massDestroy'];

	if (in_array($routeName, $create) && !auth()->user()->isAllowedTo('create-user')) {
	    return redirect()->route('admin')->with('error', 'You are not authorized to access this resource.');
	}

	if (in_array($routeName, $update) && !auth()->user()->isAllowedTo('update-user')) {
	    return redirect()->route('admin.users.users.index')->with('error', 'You are not authorized to edit users.');
	}

	if (in_array($routeName, $delete) && !auth()->user()->isAllowedTo('delete-user')) {
	    return redirect()->route('admin.users.users.index')->with('error', 'You are not authorized to delete users.');
	}

        return $next($request);
    }
}
