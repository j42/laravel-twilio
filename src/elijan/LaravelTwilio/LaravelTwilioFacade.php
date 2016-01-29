<?php namespace Elijan\LaravelTwilio;

use Illuminate\Support\Facades\Facade;

class LaravelTwilioFacade extends Facade {

    protected static function getFacadeAccessor() { return 'twilio'; }

}