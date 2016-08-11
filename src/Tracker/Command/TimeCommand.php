<?php

namespace Tracker\Command;

use Herrera\Phar\Update\Manager;
use Symfony\Component\Console\Input\InputOption;
use Herrera\Json\Exception\FileException;
use Herrera\Phar\Update\Manifest;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Tracker\Helper\CodebaseApiHelper;
use Tracker\Helper\TogglApiHelper;

class TimeCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('time-update')
            ->setDescription('Adds toggl time entries into Codebase')
            // ->addOption('major', null, InputOption::VALUE_NONE, 'Allow major version update')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
    	$cb_helper = new CodebaseApiHelper();

    	$projects = $cb_helper->projects();

    	foreach($projects as $project) {
    		$names[] = $project['name'];
    	}

    	sort($names);
    	print_r($names);

    	die;

    	$project = $cb_helper->getProjectByName('Creode');

    	die;
    	// 
        /* $output->writeln('Looking for updates...');

        try {
            $manager = new Manager(Manifest::loadFile(self::MANIFEST_FILE));
        } catch (FileException $e) {
            $output->writeln('<error>Unable to search for updates</error>');

            return 1;
        }

        $currentVersion = $this->getApplication()->getVersion();
        $allowMajor = $input->getOption('major');

        if ($manager->update($currentVersion, $allowMajor)) {
            $output->writeln('<info>Updated to latest version</info>');
        } else {
            $output->writeln('<comment>Already up-to-date</comment>');
        } */
    }
}
