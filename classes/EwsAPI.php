<?php

namespace calsync\classes;

use calsync\exceptions\ConfigException;
use jamesiarmes\PhpEws\Client;
use jamesiarmes\PhpEws\Enumeration\DefaultShapeNamesType;
use jamesiarmes\PhpEws\Enumeration\DistinguishedFolderIdNameType;
use jamesiarmes\PhpEws\Request\SyncFolderItemsType;
use jamesiarmes\PhpEws\Type\DistinguishedFolderIdType;
use jamesiarmes\PhpEws\Type\ItemResponseShapeType;
use jamesiarmes\PhpEws\Type\TargetFolderIdType;
use JsonException;


class EwsAPI extends API {

    /**
     * @var Client
     */
    private static Client $client;

    /**
     * @var string|null
     */
    private static ?string $sync_state = null;

    /**
     * @var SyncFolderItemsType
     */
    private static SyncFolderItemsType $request;


    /**
     * @return Client
     */
    public static function getClient(): Client {
        if (!empty(self::$client) && self::$client instanceof Client) {
            return self::$client;
        }

        try {
            $client = new Client(
                Config::get('outlook_host'),
                Config::get('outlook_user'),
                Config::get('outlook_pass'),
                Client::VERSION_2016
            );
            $client->setTimezone(Config::get('outlook_timezone'));
        }
        catch (ConfigException $e) {
            Core::errorOut($e->getMessage());
        }

        /** @noinspection PhpUndefinedVariableInspection */
        self::$client = $client;
        return self::$client;
    }

    /**
     * @return string|null
     */
    private static function getSyncState(): ?string {
        if (empty(self::$sync_state)) {
            $state = @file_get_contents(__DIR__ . '/../runtime/ews_sync_state.json');
            if ($state !== false) {
                try {
                    $state = json_decode(
                        $state,
                        true,
                        512,
                        JSON_THROW_ON_ERROR
                    );
                }
                catch (JsonException) {
                    Core::errorOut('invalid sync state!');
                }

                self::$sync_state = $state;
            }
        }

        return self::$sync_state;
    }

    /**
     * @param string $state
     */
    public static function setSyncState(string $state): void {
        if (!empty($state)) {
            try {
                file_put_contents(
                    __DIR__ . '/../runtime/ews_sync_state.json',
                    json_encode(
                        $state,
                        JSON_THROW_ON_ERROR
                    )
                );
            }
            catch (JsonException) {
                Core::errorOut('cannot update sync state!');
            }

            self::$sync_state = $state;
        }
    }

    /**
     * @return SyncFolderItemsType
     */
    private static function getRequest(): SyncFolderItemsType {
        if (empty(self::$request) || !(self::$request instanceof SyncFolderItemsType)) {
            $request = new SyncFolderItemsType();
            $request->SyncState = self::getSyncState();
            $request->MaxChangesReturned = 512;
            $request->ItemShape = new ItemResponseShapeType();
            $request->ItemShape->BaseShape = DefaultShapeNamesType::ALL_PROPERTIES;
            $request->SyncFolderId = new TargetFolderIdType();

            $folder = new DistinguishedFolderIdType();
            $folder->Id = DistinguishedFolderIdNameType::CALENDAR;
            $request->SyncFolderId->DistinguishedFolderId = $folder;

            self::$request = $request;
        }

        return self::$request;
    }

    /**
     * @return array
     */
    public static function getResponse(): array {
        $response = self::getClient()->SyncFolderItems(self::getRequest());
        return $response->ResponseMessages->SyncFolderItemsResponseMessage;
    }

}
