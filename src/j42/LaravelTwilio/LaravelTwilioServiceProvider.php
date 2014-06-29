<?php namespace J42\LaravelTwilio;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;

class LaravelTwilioServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot() {
		$this->package('j42/laravel-twilio', 'twilio');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register() {

		// Get pseudo-connection from config
		$config = Config::get('twilio');

		// Register Singleton
		App::singleton('twilio', function() {
			return new TwilioClient($config);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides() {
		return array('twilio');
	}

}
