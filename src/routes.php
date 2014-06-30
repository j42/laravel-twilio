<?php namespace J42\LaravelTwilio;

\Route::any('twilio/verify', [
	'uses'	=> 'J42\LaravelTwilio\TwilioVerify@verifyPhone'
]);

\Route::any('api/twilio/verify', [
	'uses'	=> 'J42\LaravelTwilio\TwilioVerify@verifyPhone'
]);
