<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            // Prevent the login page to be loaded in the document iframe.
            $route = (substr($request->path(), 0, 13) === 'cms/documents') ? 'expired' : 'login';

            return route($route);
        }
    }
}
