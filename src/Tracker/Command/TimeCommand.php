<?php

namespace Tracker\Command;

use Herrera\Phar\Update\Manager;
use Symfony\Component\Console\Input\InputOption;
use Herrera\Json\Exception\FileException;
use Herrera\Phar\Update\Manifest;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;

use Symfony\Component\Console\Input\InputArgument;

use Tracker\Helper\CodebaseApiHelper;
use Tracker\Helper\TogglApiHelper;

class TimeCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('time-update')
            ->setDescription('Adds toggl time entries into Codebase')
           	->addArgument('Date Type', InputArgument::REQUIRED, 'Which date would you like to use? Could be one of the following: (today, yesterday, custom)')
           	->addOption('Start Date', null, InputOption::VALUE_NONE, 'If custom is selected you can input a start date in format dd/mm/yyyy')
           	->addOption('End Date', null, InputOption::VALUE_NONE, 'If custom is selected you can input a end date in format dd/mm/yyyy')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
    	// In future we might not need this. We could just track if we are using an archived project and use it
    	// However this could cause more work since some archived projects have the name.
    	$question_helper = $this->getHelper('question');
    	$question = new ConfirmationQuestion('Do you wish to also time track archived Projects? (y/n) ', false);
        $archived = true;

        if (!$question_helper->ask($input, $output, $question)) {
            $archived = false;
        }

    	$cb_helper = new CodebaseApiHelper();
    	$projects = $cb_helper->projects($archived);

    	// Request options for time tracking

    	// Loop through projects
    	/* foreach($projects as $project) {
    		$names[] = $project['name'];
    	}

    	sort($names);
    	print_r($names);

    	die; */

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
