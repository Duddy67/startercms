<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminUserRoles
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

        $create = ['admin.user.roles.index', 'admin.user.roles.create', 'admin.user.roles.store'];
        $update = ['admin.user.roles.update', 'admin.user.roles.edit'];
        $delete = ['admin.user.roles.destroy', 'admin.user.roles.massDestroy'];

        if (in_array($routeName, $create) && !auth()->user()->isAllowedTo('create-role')) {
            return redirect()->route('admin')->with('error', __('messages.generic.access_not_auth'));
        }

        if (in_array($routeName, $update) && !auth()->user()->isAllowedTo('update-role')) {
            return redirect()->route('admin.user.roles.index')->with('error', __('messages.role.edit_not_auth'));
        }

        if (in_array($routeName, $delete) && !auth()->user()->isAllowedTo('delete-role')) {
            return redirect()->route('admin.user.roles.index')->with('error', __('messages.role.delete_not_auth'));
        }

        return $next($request);
    }
}
