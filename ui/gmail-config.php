<?php
/**
 * Google auth response handler
 */

require_once __DIR__ . "/../boot.php";

use Access2Me\Helper;
use Access2Me\Service\Auth\Google;

try {
    $manager = new Google($appConfig['services']['google']);
    $manager->addHandler(new Google\SenderAuthHandler($appConfig));
    $manager->addHandler(new Google\UserAuthHandler($appConfig));
    $manager->processResponse($_GET, $appConfig);
} catch (\Exception $ex) {
    Logging::getLogger()->error($ex->getMessage(), ['exception' => $ex]);
    Helper\Http::generate500();
}
