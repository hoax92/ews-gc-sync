<?php

namespace calsync;

use calsync\classes\Core;
use calsync\classes\EwsAPI;
use jamesiarmes\PhpEws\Request\SyncFolderItemsType;
use jamesiarmes\PhpEws\Enumeration\DefaultShapeNamesType;
use jamesiarmes\PhpEws\Enumeration\DistinguishedFolderIdNameType;
use jamesiarmes\PhpEws\Enumeration\ResponseClassType;
use jamesiarmes\PhpEws\Type\DistinguishedFolderIdType;
use jamesiarmes\PhpEws\Type\ItemResponseShapeType;
use jamesiarmes\PhpEws\Type\TargetFolderIdType;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/autoloader.php';


$sync_state = null;

$request = new SyncFolderItemsType();
$request->SyncState = $sync_state;
$request->MaxChangesReturned = 512;
$request->ItemShape = new ItemResponseShapeType();
$request->ItemShape->BaseShape = DefaultShapeNamesType::ALL_PROPERTIES;
$request->SyncFolderId = new TargetFolderIdType();

$folder = new DistinguishedFolderIdType();
$folder->Id = DistinguishedFolderIdNameType::CALENDAR;
$request->SyncFolderId->DistinguishedFolderId = $folder;

$response = EwsAPI::getClient()->SyncFolderItems($request);
$response_messages = $response->ResponseMessages->SyncFolderItemsResponseMessage;

foreach ($response_messages as $response_message) {
    if ($response_message->ResponseClass !== ResponseClassType::SUCCESS) {
        $code = $response_message->ResponseCode;
        $message = $response_message->MessageText;
        fwrite(STDERR, "Failed to sync folder with \"$code: $message\"\n");
        continue;
    }

    $new_sync_state = $response_message->SyncState;
    fwrite(STDOUT, "New sync state: $new_sync_state\n\n");

    fwrite(STDOUT, "The following events have been added:\n");
    foreach ($response_message->Changes->Create as $change) {
        $id = $change->CalendarItem->ItemId->Id;
        $title = $change->CalendarItem->Subject;
        fwrite(STDOUT, "- $title: $id\n");
    }

    fwrite(STDOUT, "\nThe following events have been read:\n");
    foreach ($response_message->Changes->ReadFlagChange as $change) {
        $id = $change->ItemId->Id;
        fwrite(STDOUT, "- $id\n");
    }

    fwrite(STDOUT, "\nThe following events have been updated:\n");
    foreach ($response_message->Changes->Update as $change) {
        $id = $change->CalendarItem->ItemId->Id;
        $title = $change->CalendarItem->Subject;
        fwrite(STDOUT, "- $title: $id\n");
    }

    fwrite(STDOUT, "\nThe following events have been deleted:\n");
    foreach ($response_message->Changes->Delete as $change) {
        $id = $change->ItemId->Id;
        fwrite(STDOUT, "- $id\n");
    }
}


Core::writeOut('');
