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
		        $output->writeln('<error>An error occured. Couldn\'t roll back version</error>');
		        exit;
		    }

		    $output->writeln('<info>Successfully rolled back version</info>');
		    exit;
		} catch (\Exception $e) {
		    // Report an error!
		    $output->writeln('<error>'.$e->getMessage().'</error>');
		    exit;
		}
	}
}

?>