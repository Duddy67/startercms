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

        $access = ['admin.settings.emails.index'];
        $create = ['admin.settings.emails.create', 'admin.settings.emails.store'];
        $update = ['admin.settings.emails.update', 'admin.settings.emails.edit'];
        $delete = ['admin.settings.emails.destroy', 'admin.settings.emails.massDestroy'];

	// N.B: Some admin type users might be allowed to only update email subjects and bodies. 
	//      To allow them to access the email list the update-email permission is used  
	//      as the access-email permission doesn't exists. 
	if (in_array($routeName, $access) && !auth()->user()->isAllowedTo('update-email')) {
	    return redirect()->route('admin')->with('error', __('messages.generic.access_not_auth'));
	}

	if (in_array($routeName, $create) && !auth()->user()->isSuperAdmin()) {
	    return redirect()->route('admin')->with('error', __('messages.generic.access_not_auth'));
	}

	if (in_array($routeName, $update) && !auth()->user()->isAllowedTo('update-email')) {
	    return redirect()->route('admin.settings.emails.index')->with('error', __('messages.email.edit_not_auth'));
	}

	if (in_array($routeName, $delete) &&  !auth()->user()->isSuperAdmin()) {
	    return redirect()->route('admin.settings.emails.index')->with('error', __('messages.email.delete_not_auth'));
	}

        return $next($request);
    }
}
