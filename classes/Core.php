<?php

namespace calsync\classes;


class Core {

    /**
     * @var bool
     */
    private static bool $debug = false;


    /**
     * @param string $message
     * @param bool $no_terminate
     */
    public static function errorOut(string $message, bool $no_terminate = false): void {
        if (!empty($message)) {
            fwrite(STDERR, $message . "\n");
        }

        if ($no_terminate === false) {
            exit(1);
        }
    }

    /**
     * @param string $message
     * @param bool $terminate
     */
    public static function writeOut(string $message, bool $terminate = false): void {
        if (!empty($message)) {
            fwrite(STDOUT, $message . "\n");
        }

        if ($terminate === true) {
            exit(0);
        }
    }

    /**
     * @param bool $flag
     */
    public static function setDebug(bool $flag = true): void {
        self::$debug = $flag;
    }

    public static function debugOut(string $message): void {
        if (!empty($message) && self::$debug) {
            fwrite(STDOUT, $message . "\n");
        }
    }

}
