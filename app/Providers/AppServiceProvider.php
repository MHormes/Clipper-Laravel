<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use App\Support\SeoMetadata;
use Illuminate\Support\Facades\View;

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
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        View::composer('app', function ($view) {
            $defaults = SeoMetadata::default()->toArray();
            $data = $view->getData();

            foreach ($defaults as $key => $value) {
                // If the key isn't already set by the controller, use the default
                if (!array_key_exists($key, $data)) {
                    $view->with($key, $value);
                }
            }
        });
    }
}
