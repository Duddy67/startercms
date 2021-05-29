<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class BladeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Blade::directive('allowto', function ($expression) {
	    return "<?php if (auth()->user()->isAllowedTo($expression)) : ?>";
        });

	Blade::directive('endallowto', function () {
            return "<?php endif; ?>";
        });

        Blade::directive('accessadmin', function () {
	    return "<?php if (auth()->user()->canAccessAdmin()) : ?>";
        });

	Blade::directive('endaccessadmin', function () {
            return "<?php endif; ?>";
        });

    }
}
