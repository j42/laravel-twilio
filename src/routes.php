<?php namespace Elijan\LaravelTwilio;

# Verification Routes
\Route::any('twilio/sms', [
		'uses'	=> 'Elijan\LaravelTwilio\TwilioVerify@sms'
]);

\Route::any('twilio/verify', [
	'uses'	=> 'Elijan\LaravelTwilio\TwilioVerify@verify'
]);

\Route::any('api/twilio/verify', [
	'uses'	=> 'Elijan\LaravelTwilio\TwilioVerify@verify'
]);

\Route::any('twilio/verify/twiml', [
	'uses'	=> 'Elijan\LaravelTwilio\TwilioVerify@twiml'
]);

\Route::any('api/twilio/verify/twiml', [
	'uses'	=> 'Elijan\LaravelTwilio\TwilioVerify@twiml'
]);
