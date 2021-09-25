<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMenusMenus
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

        $create = ['admin.menus.menus.index', 'admin.menus.menus.create', 'admin.menus.menus.store'];
        $update = ['admin.menus.menus.update', 'admin.menus.menus.edit'];
        $delete = ['admin.menus.menus.destroy', 'admin.menus.menus.massDestroy'];

	if (in_array($routeName, $create) && !auth()->user()->isAllowedTo('create-menu')) {
	    return redirect()->route('admin')->with('error', __('messages.generic.access_not_auth'));
	}

	if (in_array($routeName, $update) && !auth()->user()->isAllowedTo('update-menu')) {
	    return redirect()->route('admin.menus.menus.index')->with('error', __('messages.menus.edit_not_auth'));
	}

	if (in_array($routeName, $delete) && !auth()->user()->isAllowedTo('delete-menu')) {
	    return redirect()->route('admin.menus.menus.index')->with('error', __('messages.menus.delete_not_auth'));
	}

        return $next($request);
    }
}
