<?php namespace J42\LaravelTwilio;

use Illuminate;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cookie;

class TwilioVerify extends \BaseController implements TwilioVerifyInterface {

	# Properties
	public $phone;


	// Main Router
	public function verify($message = null) {

		// Verify Existing?
		if ((Cookie::has('twilio::phone')) && $this->verified()) return $this->verified();

		// Else New Request
		$method = Input::get('method');
		$phone  = preg_replace('/[^\d]+/', '', Input::get('phone'));
		if (!Input::has('phone') || strlen($phone) < 10) return $this->respond('Please supply a valid phone number.', 500);

		// Create Token
		$token = $this->createToken($phone);

		// Populate Message
		$message = (is_string($message)) ? str_ireplace('{code}', $token['token'], $message) : null;

		// Method Responder
		switch (strtolower($method)) {

			case 'sms':
				return $this->sendSms($phone, $token, $message);
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
			$response->say('Please enter the following code '.(Config::get('app.domain') ? ' on '.$this->getDomain() : '').'.');
			$response->say(implode(', ', str_split(Input::get('code'))));
			$response->say('Once again, your code is: ');
			$response->say(implode(', ', str_split(Input::get('code'))));
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
	protected function sendSms($phone, Array $token, $message = null) {

		// Response(s) Indexed by Recipient Phone #(s)
		$responses = \Twilio::sms([
			'to'		=> $phone,
			'message'	=> (is_string($message)) ? $message : $token['token']."\n\nPlease enter this code".(Config::get('app.domain') ? ' on '.$this->getDomain() : '')." to complete the verification process."
		]);

		// Update Model
		if ($responses[$phone]->status === 'queued') {
			$this->phone = Cookie::get('twilio::phone');
		}

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

		// Update Model
		if ($responses[$phone]->status === 'queued') {
			$this->phone = Cookie::get('twilio::phone');
		}

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
		$token  = ceil(mt_rand(10000,99999));
		$cookie = [
			'token'		=> $token,
			'phone'		=> [
				'code'	=> $token,
				'number'=> $phone,
				'valid'	=> false
		   ]
		];
		$cookie['cookie'] = Cookie::make('twilio::phone', $cookie['phone'], 2);
		return $cookie;
	}

	// Validate Token (TTL 5m)
	// Returns: (json || false) JSEND-compliant response or false (passthrough)
	// Args: void
	protected function verified() {

		// Valid Code || Submitted Proof?
		$payload = Cookie::get('twilio::phone');

		// Valid Request
		if ($payload['code'] == Input::get('code') ||
			$payload['valid'] === true) {

			// Validate.
			$payload['valid'] = true;

			// Update Model
			$this->phone = $payload;

			// Fire "Phone Added" Event
			\Event::fire('user.phoneVerified', $payload);

			// Respond w/ Object for a 5 Minute TTL
			return $this->respond($payload, 200)->withCookie(Cookie::make('twilio::phone', $payload, 5));

		} else return false;
	}


	// Get Domain
	// Returns: (string) $domain
	// Args: void
	private function getDomain() {
		return ucwords(parse_url(Config::get('app.domain'), PHP_URL_HOST));
	}

}
