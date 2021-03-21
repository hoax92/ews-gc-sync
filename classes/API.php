<?php

namespace calsync\classes;

use Google_Client;
use jamesiarmes\PhpEws\Client;


abstract class API {

    /**
     * @return Google_Client|Client
     */
    abstract public static function getClient(): Google_Client|Client;

}
