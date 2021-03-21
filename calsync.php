<?php

namespace calsync;

use calsync\classes\Core;
use calsync\classes\EwsAPI;
use calsync\classes\GoogleAPI;
use jamesiarmes\PhpEws\Enumeration\ResponseClassType;
use jamesiarmes\PhpEws\Response\SyncFolderItemsResponseMessageType;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/autoloader.php';


Core::setDebug();       # @TODO

GoogleAPI::getCalendar();
// GoogleAPI::createEvent($ews_data);

/**
 * @var SyncFolderItemsResponseMessageType $item
 */
foreach (EwsAPI::getResponse() as $item) {
    if ($item->ResponseClass !== ResponseClassType::SUCCESS) {
        $code = $item->ResponseCode;
        $message = $item->MessageText;
        Core::errorOut("failed to sync folder with '$code: $message'", true);
        continue;
    }

    $sync_state = $item->SyncState;
    Core::debugOut("new sync_state: $sync_state");
    EwsAPI::setSyncState($sync_state);

    Core::debugOut('count new: ' . count($item->Changes->Create));

    foreach ($item->Changes->Create as $change) {
        GoogleAPI::createEvent($change);
    }
}


Core::writeOut('');
