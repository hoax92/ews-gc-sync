<?php

namespace calsync\classes;

use calsync\exceptions\ConfigException;
use Google\Exception;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Calendar;
use Google_Service_Calendar_Event;
use Google_Service_Calendar_EventDateTime;
use jamesiarmes\PhpEws\Type\SyncFolderItemsCreateOrUpdateType;
use JsonException;


class GoogleAPI extends API {

    /**
     * @var Google_Client
     */
    private static Google_Client $client;

    /**
     * @var Google_Service_Calendar
     */
    private static Google_Service_Calendar $service;

    /**
     * @var string
     */
    private static string $cal_id;


    /**
     * @param bool $auth
     * @return Google_Client
     */
    public static function getClient(bool $auth = true): Google_Client {
        if (
            empty(self::$client) ||
            !(self::$client instanceof Google_Client) ||
            $auth === false
        ) {
            $client = new Google_Client();

            try {
                $client->setAuthConfig(__DIR__ . '/../runtime/client_secret.json');
            }
            catch (Exception $e) {
                Core::errorOut($e->getMessage());
            }

            $client->addScope(Google_Service_Calendar::CALENDAR);
            $client->setRedirectUri('http://localhost:8080/token.php');
            $client->setAccessType('offline');
            $client->setApprovalPrompt('force');

            self::$client = $client;
        }

        if ($auth === true) {
            self::handleToken(self::$client);
        }

        return self::$client;
    }

    /**
     * @param Google_Client $client
     */
    private static function handleToken(Google_Client $client): void {
        $token = @file_get_contents(__DIR__ . '/../runtime/ga_token.json');
        if ($token === false) {
            Core::errorOut('token not found!');
        }

        try {
            $token = json_decode($token, true, 512, JSON_THROW_ON_ERROR);
        }
        catch (JsonException) {
            Core::errorOut('invalid token!', true);
        }

        if (!empty($token)) {
            $client->setAccessToken($token);
            if ($client->isAccessTokenExpired()) {
                Core::debugOut('token expired, refreshing...');

                $refresh_token = $client->getRefreshToken();
                Core::debugOut("refresh token: $refresh_token");

                $token = $client->fetchAccessTokenWithRefreshToken($refresh_token);
                Core::debugOut('new token: ' . var_export($token, true));

                self::storeToken($token);
            }
        }
        else {
            $auth_url = $client->createAuthUrl();
            Core::writeOut($auth_url, true);
        }
    }

    /**
     * @param array $token
     */
    public static function storeToken(array $token): void {
        try {
            file_put_contents(
                __DIR__ . '/../runtime/ga_token.json',
                json_encode(
                    $token,
                    JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT
                )
            );
        }
        catch (JsonException) {
            Core::errorOut('failed storing token!');
        }
    }

    /**
     * @return Google_Service_Calendar
     */
    private static function getService(): Google_Service_Calendar {
        if (empty(self::$service) || !(self::$service instanceof Google_Service_Calendar)) {
            self::$service = new Google_Service_Calendar(self::getClient());
        }

        return self::$service;
    }

    /**
     * @return string
     */
    public static function getCalendar(): string {
        if (empty(self::$cal_id)) {
            $list = self::getService()->calendarList->listCalendarList();
            $cal_id = '';
            foreach ($list->getItems() as $item) {
                try {
                    if ($item->getSummary() === Config::get('google_calendar')) {
                        $cal_id = $item->getId();
                        Core::debugOut("cal found: $cal_id");
                        break;
                    }
                }
                catch (ConfigException $e) {
                    Core::errorOut($e->getMessage());
                }
            }

            if (empty($cal_id)) {
                # calendar doesn't exist
                $cal = new Google_Service_Calendar_Calendar();

                try {
                    $cal->setSummary(Config::get('google_calendar'));
                    $cal->setTimeZone(Config::get('google_timezone'));
                }
                catch (ConfigException $e) {
                    Core::errorOut($e->getMessage());
                }

                $cal_id = self::getService()->calendars->insert($cal)->getId();
                Core::debugOut("cal created: $cal_id");
            }

            self::$cal_id = $cal_id;
        }

        return self::$cal_id;
    }

    /**
     * @param SyncFolderItemsCreateOrUpdateType $data
     */
    public static function createEvent(SyncFolderItemsCreateOrUpdateType $data): void {
        $event = new Google_Service_Calendar_Event();
        $event->setSummary($data->CalendarItem->Subject);

        try {
            $timezone = Config::get('google_timezone');
        }
        catch (ConfigException $e) {
            Core::errorOut($e->getMessage());
        }

        $start = new Google_Service_Calendar_EventDateTime();
        $start->setDateTime($data->CalendarItem->Start);
        /** @noinspection PhpUndefinedVariableInspection */
        $start->setTimeZone($timezone);
        $event->setStart($start);

        $end = new Google_Service_Calendar_EventDateTime();
        $end->setDateTime($data->CalendarItem->End);
        $end->setTimeZone($timezone);
        $event->setEnd($end);

        # free != busy
        $event->setTransparency('transparent');

        self::getService()->events->insert(self::getCalendar(), $event);
    }

}
