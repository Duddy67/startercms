<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Traits\Admin\RolesPermissions;
use App\Models\Settings\General;
use Cache;


class Admin
{
    use RolesPermissions;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (in_array($this->getUserRoleType(auth()->user()), ['super-admin', 'admin', 'manager', 'assistant'])) {

	    $settings = Cache::rememberForever('settings', function () {
	        // Updates the config app parameters.
		return  General::getAppSettings();
	    });

	    config($settings); // Any DB settings will overwrite app config

	    return $next($request);
	}

	return redirect()->route('profile');
    }
}
