<?php

/**
 * start webserver in the current dir via
 * $ php -S localhost:8080
 */

use calsync\classes\GoogleAPI;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/autoloader.php';


if (!empty($_GET['code'])) {
    $token = GoogleAPI::getClient(false)
        ->fetchAccessTokenWithAuthCode($_GET['code']);

    echo '<pre>', var_export($token, true), '</pre>';

    GoogleAPI::storeToken($token);
}
