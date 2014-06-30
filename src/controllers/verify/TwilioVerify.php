<?php namespace J42\LaravelTwilio;

use Illuminate;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cookie;

class TwilioVerify extends \BaseController implements TwilioVerifyInterface {

	// Main Router
	public function verifyPhone() {

		// Verify Existing?
		if ((Cookie::has('twilio::phone')) && $this->verified()) return $this->verified();

		// Else New Request
		$method = Input::get('method');
		$phone  = preg_replace('/[^\d]+/', '', Input::get('phone'));
		if (!Input::has('phone') || strlen($phone) < 10) return $this->respond('Please supply a valid phone number.', 500);

		// Create Token
		$token = $this->createToken($phone);

		// Method
		switch (strtolower($method)) {

			// Verify by Text
			case 'sms':
				return $this->sendSms($phone, $token);
				break;

			case 'phone':
				// Respond
				return $this->respond('Temporarily disabled.', 500);
				break;

			default:
				// Respond
				return $this->respond('Please choose a valid verification method.', 500);
				break;

		}

		// Return Default Error
		return $this->respond('Malformed request.', 500);

	}


	// Response Handler
	// Returns: (json) JSEND-compliant response {success: ..., data: ...}
	// Args: (mixed) $data, (int) status code
	private function respond($data, $code = 200) {

		switch ($code) {
			case 200: $status = 'success'; break;
			case 500: $status = 'error'; break;
		}

		return Response::json(compact('status', 'data'));

	}

	// Send SMS
	// Returns: (json) JSEND-compliant response {success: ..., data: ...}
	// Args: (string) $phone, (Array) $token
	private function sendSms($phone, Array $token) {

		// Response(s) Indexed by Recipient Phone #(s)
		$responses = \Twilio::sms([
			'to'		=> $phone,
			'message'	=> "Please enter the following code".(Config::get('app.domain') ? ' on '.ucwords(parse_url(Config::get('app.domain'), PHP_URL_HOST)) : '')." to complete the verification process:\n\n".$token['token']
		]);

		// Respond w/ 2 Minute TTL
		return $this->respond([
			'phone'		=> $phone,
			'message'	=> (isset($responses[$phone])) ? $responses[$phone]->status : null
		], 200)->withCookie($token['cookie']);
	}

	// Create Token (TTL 2m)
	// Returns: (Array) $token
	// Args: (string) $phone
	private function createToken($phone) {
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
	private function verified() {

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

}