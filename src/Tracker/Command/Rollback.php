<?php

use Humbug\SelfUpdate\Updater;

$updater = new Updater();
try {
    $result = $updater->rollback();
    if (! $result) {
        // report failure!
        exit 1;
    }
    exit 0;
} catch (\Exception $e) {
    // Report an error!
    exit 1;
}

?>