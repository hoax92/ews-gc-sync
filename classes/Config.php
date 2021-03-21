<?php

namespace calsync\classes;

use calsync\exceptions\ConfigException;
use JsonException;
use stdClass;


class Config {

    /**
     * @var stdClass
     */
    private static stdClass $config;


    /**
     * @return stdClass
     * @throws ConfigException
     */
    private static function getConfig(): stdClass {
        if (!empty(self::$config) && is_array(self::$config)) {
            return self::$config;
        }

        $config_default = self::loadConfig('config_default');
        $config_local = self::loadConfig('config');
        $config = (object)array_merge($config_default, $config_local);

        self::$config = $config;
        return self::$config;
    }

    /**
     * @param string $file
     *
     * @return array
     * @throws ConfigException
     */
    private static function loadConfig(string $file): array {
        $config = @file_get_contents(__DIR__ . "/../runtime/$file.json");
        if ($config === false) {
            throw new ConfigException('cannot load config');
        }

        try {
            $config = json_decode($config, true, 512, JSON_THROW_ON_ERROR);
        }
        catch (JsonException) {
            throw new ConfigException('cannot read config');
        }

        return $config;
    }

    /**
     * @param string $key
     *
     * @return string
     * @throws ConfigException
     */
    public static function get(string $key): string {
        return self::getConfig()->$key ?? '';
    }

}
