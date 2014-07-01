<?php namespace J42\LaravelTwilio;

class TwilioClient {

	# Properties
	protected $twilio;			// Services_Twilio (Twilio REST Client)
	protected $from = null;		// Initiating #
	protected $to = [];			// Recipient #s
	protected $response = [];	// Response (by #)


	// Return: (obj) TwilioClient
	// Args: (Array) $config
	public function __construct(Array $config) {

		// Sanity Check
		if (!is_string($config['key']) || !is_string($config['token'])) throw new \UnexpectedValueException('Please make sure valid API keys and tokens are set.');

		// Create Client
		$this->twilio = $this->createClient($config);

		// Set Default From?
		if (!empty($config['from'])) $this->setFrom($config['from']);

	}


	// Return: (Array) response, indexed by phone #
	// Args: (Array) $options, (callable) $callback
	public function sms(Array $options, callable $callback = null) {

		// Configure Message
		if (isset($options['to'])) $this->setTo($options['to']);
		if (isset($options['from'])) $this->setFrom($options['from']);

		// Configure Response Handler
		$this->response = [];

		// Sanity Checks
		if (empty($this->to) || !is_array($this->to)) throw new \UnexpectedValueException('Please enter recipients.');
		if (!is_string($this->from)) throw new \UnexpectedValueException('Please configure a valid "from" address.');
		if (!is_string($options['message'])) throw new \UnexpectedValueException('Please enter a message.');

		// Message Loop
		foreach ($this->to as $number) {
			// Send Via Client
			$this->response[$number] = $this->twilio->account->messages->sendMessage($this->from, $number, $options['message']);
		}

		// Callback Attempt
		if (is_callable($callback)) {
			call_user_func_array($callback, [$this->response]);
		}

		return $this->response;

	}


	// Return: (Array) responses, indexed by phone #
	// Args: (Array) $options, (callable) $callback
	public function call(Array $options, callable $callback = null) {

		// Configure Message
		if (isset($options['to'])) $this->setTo($options['to']);
		if (isset($options['from'])) $this->setFrom($options['from']);

		// Configure Response Handler
		$this->response = [];

		// Sanity Checks
		if (empty($this->to) || !is_array($this->to)) throw new \UnexpectedValueException('Please enter recipients.');
		if (!is_string($this->from)) throw new \UnexpectedValueException('Please configure a valid "from" address.');
		if (!is_string($options['twiml'])) throw new \UnexpectedValueException('Please enter a valid TWIML endpoint.');

		// Message Loop
		foreach ($this->to as $number) {
			// Send Via Client
			$this->response[$number] = $this->twilio->account->calls->create($this->from, $number, $this->toAbsoluteUrl($options['twiml']));
		}

		// Callback Attempt
		if (is_callable($callback)) {
			call_user_func_array($callback, [$this->response]);
		}

		return $this->response;

	}


	// Return: (Array) list of numbers (->phone_number)
	// Args: (Array) $options
	public function numbersNear(Array $options, Array $features = null, $buy = false) {
		$features = (is_array($features)) ? $features : Config::get('laravel-twilio::features');
		$features = (is_array($features)) ? $features : [];
		$found = $this->twilio->account->available_phone_numbers->getList('US', 'Local', $options + $features);
		// Purchase {n} numbers?
		if ($buy && $buy > 0) {
			return $this->buyNumber(array_chunk($found->available_phone_numbers, intval($buy))[0]);
		}
		// Return available phone numbers
		return $found->available_phone_numbers;
	}


	// Return: (Array) responses, indexed by phone #
	// Args:: (Array || string) $number
	public function buyNumber($number) {
		$number 	= (is_array($number)) ? $number : [$number];
		$responses 	= [];
		foreach ($number as $n) {
			$responses[$n] = $this->twilio->account->incoming_phone_numbers->create([
				'PhoneNumber'	=> $n
			]);
		}
		return $responses;
	}


	// Return: (obj) $this
	// Args: (Array || string) $to
	public function setTo($to) {

		// Inject
		if (is_string($to)) {
			$this->to[] = $to;
		} elseif (is_array($to)) {
			$this->to += $to;
		}

		$this->to = array_unique($this->to);

		return $this;
	}


	// Return: (obj) $this
	// Args: (string) $from
	public function setFrom($from) {

		// Inject
		if (is_string($from)) {
			$this->from = $from;
		}

		return $this;
	}


	// Return: (obj) Services_Twilio [Twilio REST Client]
	// Args: (Array) $config [key, token]
	private function createClient(Array $config) {
		return new \Services_Twilio($config['key'], $config['token']);
	}

	// Return: (string) Absolute Path URL
	// Args: (string) URL
	private function toAbsoluteUrl($path) {
		$isUrl = (preg_match('/^https?\:\/\//', $path));
		return ($isUrl) ? $path : Config::get('laravel-twilio::twiml').ltrim($path, ' /');
	}


}