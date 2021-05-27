<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class AdminUsers
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

        $create = ['admin.users.index', 'admin.users.create', 'admin.users.store'];
        $update = ['admin.users.update', 'admin.users.edit'];
        $delete = ['admin.users.destroy', 'admin.users.massDestroy'];

	if (in_array($routeName, $create) && !auth()->user()->isAllowedTo('create-user')) {
	    return redirect()->route('admin')->with('error', 'You are not authorized to access this resource.');
	}

	if (in_array($routeName, $update) && !auth()->user()->isAllowedTo('update-user')) {
	    return redirect()->route('admin.users.index')->with('error', 'You are not authorized to edit users.');
	}

	if (in_array($routeName, $delete) && !auth()->user()->isAllowedTo('delete-user')) {
	    return redirect()->route('admin.users.index')->with('error', 'You are not authorized to delete users.');
	}

        return $next($request);
    }
}
