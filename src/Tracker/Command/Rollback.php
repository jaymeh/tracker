<?php

namespace Tracker\Command;

use Humbug\SelfUpdate\Updater;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Rollback extends Command {
	protected function configure() {
        $this
            ->setName('rollback')
            ->setDescription('Rolls back a failed update')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
		$updater = new Updater();
		try {
		    $result = $updater->rollback();
		    if (! $result) {
		        // report failure!
		        exit;
		    }
		    exit;
		} catch (\Exception $e) {
		    // Report an error!
		    exit;
		}
	}
}

?>