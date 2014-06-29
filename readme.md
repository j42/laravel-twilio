laravel-twilio
================

A Twilio port for Laravel (4.2+)


##Configuration


Add the following line to your `composer.json` and run composer update:

	{
	  "require": {
	    "j42/laravel-twilio": "dev-master"
	  }
	}

Then add the service providers and facades to `config/app.php`

```php
	'J42\LaravelTwilio\LaravelTwilioServiceProvider',
```
...
```php
	'Twilio'		  => 'J42\LaravelTwilio\LaravelTwilioFacade'
```



## SMS

How to interact with Twilio's REST-based SMS methods.

####Send an SMS

```php
	Twilio::sms([
		// Array of recipients
		'to'		=> ['19999999999'],
		// Text Message
		'message'	=> 'Contents of the text message go here'
	]);
```