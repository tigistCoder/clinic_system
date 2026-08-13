<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Blade::directive('role', function ($expression) {
            return "<?php if(auth()->check() && auth()->user()->hasRole({$expression})): ?>";
        });

        \Illuminate\Support\Facades\Blade::directive('endrole', function () {
            return '<?php endif; ?>';
        });

        \Illuminate\Support\Facades\Blade::directive('unlessrole', function ($expression) {
            return "<?php if(auth()->check() && !auth()->user()->hasRole({$expression})): ?>";
        });

        \Illuminate\Support\Facades\Blade::directive('endunlessrole', function () {
            return '<?php endif; ?>';
        });
    }
}
