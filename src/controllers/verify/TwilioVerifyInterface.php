<?php namespace Elijan\LaravelTwilio;

interface TwilioVerifyInterface {

	// Single Router
	public function verify($message = null);
	public function twiml();

}