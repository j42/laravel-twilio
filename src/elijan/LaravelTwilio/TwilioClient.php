<?php namespace Elijan\LaravelTwilio;

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
            try {
                $this->response[$number] = $this->twilio->account->messages->sendMessage($this->from, $number, $options['message']);
            } catch (\Exception $e) {

                $this->response['error'] = true;
                $this->response[$number] = false;
                $this->response['message'] = $e->getMessage();
                \Log::error($e->getMessage());
            }
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
            try {
                $this->response[$number] = $this->twilio->account->calls->create($this->from, $number, $this->toAbsoluteUrl($options['twiml']));
            } catch (\Exception $e) {
                $this->response[$number] = false;
                \Log::error($e->getMessage());
            }
        }

        // Callback Attempt
        if (is_callable($callback)) {
            call_user_func_array($callback, [$this->response]);
        }

        return $this->response;

    }


    // Return: (Array) list of numbers (->phone_number)
    // Args: (Array) $options [search options], (Array) $features [number features & config], (int) # of numbers to acquire
    public function numbersNear(Array $options, Array $features = null, $buy = false) {
        $features = (is_array($features)) ? $features : \Config::get('laravel-twilio::features');
        $features = (is_array($features)) ? $features : [];
        $found = $this->twilio->account->available_phone_numbers->getList('US', 'Local', $options + $features);
        // Purchase {n} numbers?
        if ($buy && $buy > 0 && is_array($found->available_phone_numbers) && !empty($found->available_phone_numbers)) {
            $purchase = array_chunk($found->available_phone_numbers, intval($buy));
            if (!empty($purchase)) {
                return $this->buyNumber($purchase[0], $features);
            } else {
                \Log::error('No available phone numbers', [$found->available_phone_numbers]);
            }
        }
        // Return available phone numbers
        return (is_array($found->available_phone_numbers)) ? $found->available_phone_numbers : [];
    }


    // Return: (Array) responses, indexed by phone #
    // Args: (Array || string) $number, (Array) $config
    public function buyNumber($number, Array $config = null, $friendly = true) {
        $friendly 	= ($friendly) ? 'friendly_name' : 'phone_number';
        $number 	= (is_array($number)) ? $number : [$number];
        $config 	= (is_array($config)) ? $config : [];
        $responses 	= [];
        foreach ($number as $n) {

            if ($n) {
                $string = (is_string($n)) ? $n : (is_object($n) ? $n->{$friendly} : $n[0]->{$friendly});
                try {
                    $responses[$string] = $this->twilio->account->incoming_phone_numbers->create([
                            'PhoneNumber'	=> (string) $string
                        ] + $config);
                } catch (\Exception $e) {
                    $responses[$string] = false;
                    \Log::error('Error purchasing number.', [$n]);
                }
            }

        }
        return $responses;
    }


    // Return: (Array) responses
    // Args: (Array || string) $numbers, (Array) $config
    public function releaseNumber($number, Array $config = null) {
        $number 	= (is_array($number)) ? $number : [$number];
        $config 	= (is_array($config)) ? $config : [];
        $responses 	= [];
        foreach ($number as $n) {

            if ($n) {
                $string = (is_string($n)) ? $n : (is_object($n) ? $n->phone_number : $n[0]->phone_number);
                try {
                    $obj = $this->twilio->account->incoming_phone_numbers->getNumber((string) $string);
                    $responses[$string] = (!empty($obj->sid)) ? $this->twilio->account->incoming_phone_numbers->delete($obj->sid) : 'failed';
                } catch (\Exception $e) {
                    $responses[$string] = false;
                    \Log::error('Error releasing number.', [$n]);
                }
            }

        }
        return $responses;
    }

    // Return: (Array) numbers
    // Args: (Array) numbers, (Array) resource configuration
    public function update(Array $numbers, Array $config) {
        foreach ($numbers as $n) {
            $sid = (is_object($n) && !empty($n->sid)) ? $n->sid : false;
            $sid = ($sid) ? $sid : (is_array($n) && !empty($n['sid']) ? $n['sid'] : false);
            if ($sid) {
                $Number = $this->twilio->account->incoming_phone_numbers->get($sid);
                $Number->update($config);
            }
        }
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
        return ($isUrl) ? $path : \Config::get('laravel-twilio::twiml').ltrim($path, ' /');
    }


}
