<?php
/**
 * Linkedin auth response handler
 */

require_once __DIR__ . "/boot.php";

use Access2Me\Helper;
use Access2Me\Service\Auth\Linkedin;

$db = new Database;

try {
    $manager = new Linkedin($appConfig['services']['linkedin']);
    $manager->addHandler(new Linkedin\SenderAuthHandler($appConfig));
    $manager->addHandler(new Linkedin\UserAuthHandler($appConfig));
    $manager->processResponse($_GET, $appConfig);
} catch (\Exception $ex) {
    Logging::getLogger()->error($ex->getMessage(), ['exception' => $ex]);
    Helper\Http::generate500();
}
