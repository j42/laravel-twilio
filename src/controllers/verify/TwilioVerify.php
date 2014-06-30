<?php namespace J42\LaravelTwilio;

use Illuminate;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cookie;

class TwilioVerify extends \BaseController implements TwilioVerifyInterface {

	// Main Router
	public function verify() {

		// Verify Existing?
		if ((Cookie::has('twilio::phone')) && $this->verified()) return $this->verified();

		// Else New Request
		$method = Input::get('method');
		$phone  = preg_replace('/[^\d]+/', '', Input::get('phone'));
		if (!Input::has('phone') || strlen($phone) < 10) return $this->respond('Please supply a valid phone number.', 500);

		// Create Token
		$token = $this->createToken($phone);

		// Method Responder
		switch (strtolower($method)) {

			case 'sms':
				return $this->sendSms($phone, $token);
				break;

			case 'call':
				return $this->sendCall($phone, $token);
				break;

			default:
				return $this->respond('Please choose a valid verification method.', 500);
				break;

		}

		// Return Default Error
		return $this->respond('Malformed request.', 500);

	}


	// Twiml
	public function twiml() {
		if (Input::has('code')) {
			$response = new \Services_Twilio_Twiml();
			$response->say('Please enter the following code on '.(Config::get('app.domain') ? ' on '.$this->getDomain() : '').'.');
			$response->say(implode(' ', str_split(Input::get('code'))));
			print $response;
		} else return false;
	}


	// Response Handler
	// Returns: (json) JSEND-compliant response {success: ..., data: ...}
	// Args: (mixed) $data, (int) status code
	protected function respond($data, $code = 200) {

		switch ($code) {
			case 200: $status = 'success'; break;
			case 500: $status = 'error'; break;
		}

		return Response::json(compact('status', 'data'));

	}

	// Send SMS
	// Returns: (json) JSEND-compliant response {success: ..., data: ...}
	// Args: (string) $phone, (Array) $token
	protected function sendSms($phone, Array $token) {

		// Response(s) Indexed by Recipient Phone #(s)
		$responses = \Twilio::sms([
			'to'		=> $phone,
			'message'	=> "Please enter the following code".(Config::get('app.domain') ? ' on '.$this->getDomain() : '')." to complete the verification process:\n\n".$token['token']
		]);

		// Respond w/ 2 Minute TTL
		return $this->respond([
			'phone'		=> $phone,
			'status'	=> (isset($responses[$phone])) ? $responses[$phone]->status : null
		], 200)->withCookie($token['cookie']);
	}

	// Send Call
	// Returns: (json) JSEND-compliant response {success: ..., data: ...}
	// Args: (string) $phone, (Array) $token
	protected function sendCall($phone, Array $token) {

		$responses = \Twilio::call([
			'to'	=> $phone,
			'twiml'	=> Config::get('laravel-twilio::twiml').'/twilio/verify/twiml?code='.$token['token']
		]);

		// Respond w/ 2 Minute TTL
		return $this->respond([
			'phone'		=> $phone,
			'status'	=> (isset($responses[$phone])) ? $responses[$phone]->status : null
		], 200)->withCookie($token['cookie']);

	}

	// Create Token (TTL 2m)
	// Returns: (Array) $token
	// Args: (string) $phone
	protected function createToken($phone) {
		// Generate a Random Token w 2 Minute TTL
		$token = ceil(mt_rand(10000,99999));
		return [
			'token'		=> $token,
			'cookie'	=> Cookie::make('twilio::phone', [
								'code'	=> $token,
								'phone'	=> $phone,
								'valid'	=> false
						   ], 2)
		];
	}

	// Validate Token (TTL 5m)
	// Returns: (json || false) JSEND-compliant response or false (passthrough)
	// Args: void
	protected function verified() {

		// Valid Code || Submitted Proof?
		$cookie = Cookie::get('twilio::phone');

		// Valid Request
		if ($cookie['code'] == Input::get('code') ||
			$cookie['valid'] === true) {

			// Validate.
			$cookie['valid'] = true;

			// Respond w/ Object for a 5 Minute TTL
			return $this->respond($cookie, 200)->withCookie(Cookie::make('twilio::phone', $cookie, 5));

		} else return false;
	}


	// Get Domain
	// Returns: (string) $domain
	// Args: void
	private function getDomain() {
		return ucwords(parse_url(Config::get('app.domain'), PHP_URL_HOST));
	}

}