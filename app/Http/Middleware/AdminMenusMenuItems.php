<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMenusMenuItems
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

        $create = ['admin.menus.menuitems.index', 'admin.menus.menuitems.create', 'admin.menus.menuitems.store'];
        $update = ['admin.menus.menuitems.update', 'admin.menus.menuitems.edit'];
        $delete = ['admin.menus.menuitems.destroy', 'admin.menus.menuitems.massDestroy'];

	if (in_array($routeName, $create) && !auth()->user()->isAllowedTo('create-menu')) {
	    return redirect()->route('admin')->with('error', __('messages.generic.access_not_auth'));
	}

	if (in_array($routeName, $update) && !auth()->user()->isAllowedTo('update-menu')) {
	    return redirect()->route('admin.menus.menuitems.index')->with('error', __('messages.menus.edit_not_auth'));
	}

	if (in_array($routeName, $delete) && !auth()->user()->isAllowedTo('delete-menu')) {
	    return redirect()->route('admin.menus.menuitems.index')->with('error', __('messages.menus.delete_not_auth'));
	}

        return $next($request);
    }
}
