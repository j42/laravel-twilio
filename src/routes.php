<?php namespace J42\LaravelTwilio;

# Verification Routes
\Route::any('twilio/sms', [
		'uses'	=> 'J42\LaravelTwilio\TwilioVerify@sms'
]);

\Route::any('twilio/verify', [
	'uses'	=> 'J42\LaravelTwilio\TwilioVerify@verify'
]);

\Route::any('api/twilio/verify', [
	'uses'	=> 'J42\LaravelTwilio\TwilioVerify@verify'
]);

\Route::any('twilio/verify/twiml', [
	'uses'	=> 'J42\LaravelTwilio\TwilioVerify@twiml'
]);

\Route::any('api/twilio/verify/twiml', [
	'uses'	=> 'J42\LaravelTwilio\TwilioVerify@twiml'
]);
