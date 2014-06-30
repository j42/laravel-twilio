laravel-twilio
================

A Twilio port for Laravel (4.2+)


##Configuration


Add the package to your `composer.json` and run composer update:

	{
	  "require": {
	  	...
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


## Configuration

Generate the package config files by running `php artisan config:publish j42/laravel-twilio`, and adjust the relevant fields:

```php
	return [

		'key'	=> 'YOURAPIKEY',			// Public key
		'token'	=> 'YOURSECRETTOKEN',		// Private key
		'from'	=> '9999999999'				// Default From Address 

	];
```

## Phone Verification

Since phone verification is such a common use case, I created a simple flow to automate this.

This package automatically installs the following routes (`GET` or `POST` allowed):

	/twilio/verify

#### Get a Token

Initiate an HTTP `GET` or `POST` request to `/twilio/verify` with the following parameters:

- `phone` phone number (parsed as string)
- `method` ('sms' or 'call')

The numeric token is set in a cookie and has a 2 minute TTL during which it is valid.

**Returns:**

```php
	// Get token (method can be either 'sms' or 'call')
	file_get_contents('/twilio/verify?phone=<phone number>&method=sms');
	
	/* 
		{
			status: 'success',
			data: {
				phone: '0000000000',	// User's phone
				message: 'queued'		// Twilio response status
			}
		}
	*/
```

#### Verify a Token

Initiate an HTTP `GET` or `POST` re	uest to `/twilio/verify/` with the following parameters:

- `code` numeric code entered by user


#### Post-Verification (Success)

Once the code has been confirmed, the verified data is available via `Cookie` with a 5 minute TTL.  An HTTP request to `/twilio/verify` will return:

```php
	// Get token (method can be either 'sms' or 'call')
	file_get_contents('/twilio/verify?phone=<phone number>&method=sms');
	
	/*
		{
			status: 'success',
			data: {
				code: '00000',			// Generated Numeric Code
				phone: '0000000000',	// User's phone
				valid: true
			}
		}
	*/
```


#### Advanced Usage

Sometimes you may need to handle additional logic in a controller of your own.  By including a handy interface, this becomes easy:

1. Define the route overrides (whichever suits your preference, or, both)

```php
	\Route::any('twilio/verify', [
		'uses'	=> 'YourController@verifyPhone'
	]);

	\Route::any('api/twilio/verify', [
		'uses'	=> 'YourController@verifyPhone'
	]);
```

2. Create your controller, extending `J42\LaravelTwilio\TwilioVerify`

```php
	use J42\LaravelTwilio\TwilioVerify;

	class TwilioController extends TwilioVerify {

		// Verify Phone
		public function verifyPhone() {
			
			// Your pre-verification logic

			// Wrap Parent Function
			$response = parent::verifyPhone();

			// Your post-verification logic

			return $response;

		}

	}
```

3. Define your functionality as needed, making sure to call `parent::verifyPhone();` to handle the default events.  **If you need to access the cookie directly you may do so via: `Cookie::get('twilio::phone')`.**


## SMS

How to interact with Twilio's REST-based SMS methods.

####Send an SMS

```php
Twilio::sms([
	// From (optional -- if unsupplied, will be taken from default Config::get('twilio::config.from'))
	'from'		=> '<your twilio #>'
	// Array of recipients
	'to'		=> ['19999999999'],
	// Text Message
	'message'	=> 'Contents of the text message go here'
]);
```