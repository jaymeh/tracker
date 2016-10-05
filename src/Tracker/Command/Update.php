<?php

use Humbug\SelfUpdate\Updater;

$updater = new Updater();
$updater->getStrategy()->setPharUrl('https://jamiesykescreode.github.io/tracker/tracker.phar');
$updater->getStrategy()->setVersionUrl('https://jamiesykescreode.github.io/tracker/tracker.phar.version');
try {
    $result = $updater->update();
    if (! $result) {
        // No update needed!
        exit;
    }
    $new = $updater->getNewVersion();
    $old = $updater->getOldVersion();
    printf('Updated from %s to %s', $old, $new);
    exit;
} catch (\Exception $e) {
    // Report an error!
    exit;
}

?>