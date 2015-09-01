<?php namespace J42\LaravelTwilio;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;

class LaravelTwilioServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        //$this->package('j42/laravel-twilio');

        if (!$this->app->routesAreCached()) {
            require __DIR__ . '/../../routes.php';
        }

        $this->publishes([
            __DIR__ . '/../../config/config.php' => config_path('twilio.php'),
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {

        $this->mergeConfigFrom(
            __DIR__ . '/../../config/config.php', 'twilio'
        );

        // Configure Local Config Namespace
        //Config::addNamespace('twilio', );

        // Register Singleton
        $this->app->singleton('twilio', function ($app) {

            // Create Configuration
            $config = [
                'key' => Config::get('twilio.key'),
                'token' => Config::get('twilio.token'),
                'from' => Config::get('twilio.from')
            ];

            // Get Twilio Config
            return new TwilioClient($config);

        });

        /*$this->app->bind('twilio', function()
        {
            return new \J42\LaravelTwilio\LaravelTwilioFacade;
        });*/
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('twilio');
    }

}
