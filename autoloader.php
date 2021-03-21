<?php

spl_autoload_register(static function($classname) {
    $filename = str_replace(array('calsync', '\\'), array('', '/'), $classname);
    $filename .= '.php';
    /** @noinspection PhpIncludeInspection */
    require_once __DIR__ . $filename;
});
