<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminSettingsEmails
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

        $create = ['admin.settings.emails.index', 'admin.settings.emails.create', 'admin.settings.emails.store'];
        $update = ['admin.settings.emails.update', 'admin.settings.emails.edit'];
        $delete = ['admin.settings.emails.destroy', 'admin.settings.emails.massDestroy'];

	if (in_array($routeName, $create) && !auth()->user()->isAllowedTo('create-email')) {
	    return redirect()->route('admin')->with('error', __('messages.generic.access_not_auth'));
	}

	if (in_array($routeName, $update) && !auth()->user()->isAllowedTo('update-email')) {
	    return redirect()->route('admin.settings.emails.index')->with('error', __('messages.emails.edit_not_auth'));
	}

	if (in_array($routeName, $delete) && !auth()->user()->isAllowedTo('delete-email')) {
	    return redirect()->route('admin.settings.emails.index')->with('error', __('messages.emails.delete_not_auth'));
	}

        return $next($request);
    }
}
