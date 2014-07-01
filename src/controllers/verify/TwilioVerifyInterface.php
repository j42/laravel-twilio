<?php namespace J42\LaravelTwilio;

interface TwilioVerifyInterface {

	// Single Router
	public function verify($message = null);
	public function twiml();

}