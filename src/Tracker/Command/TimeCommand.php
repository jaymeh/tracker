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
           	->addArgument('Start Date', InputArgument::OPTIONAL, 'If custom is selected you can input a start date in format (dd/mm/yyyy)')
           	->addArgument('End Date', InputArgument::OPTIONAL, 'If custom is selected you can input a end date in format (dd/mm/yyyy)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        date_default_timezone_set('Europe/London');

        // Figure out which date type we are using
        $date_type = $input->getArgument('Date Type');

        switch($date_type) {
            case 'today':
                // Get start of day
                $start_date = new \DateTime('today');
                $start_date->setTimezone(new \DateTimeZone('Europe/London'));
                $start_date_formatted = $start_date->format(\DateTime::ATOM);
                // Get end of day
                $end_date = new \DateTime('today');
                $end_date->setTimezone(new \DateTimeZone('Europe/London'));
                $end_date->setTime(23, 59, 59);
                $end_date_formatted = $end_date->format(\DateTime::ATOM);

                break;
            case 'yesterday':
                // Get start of day
                $start_date = new \DateTime('yesterday');
                $start_date->setTimezone(new \DateTimeZone('Europe/London'));
                $start_date_formatted = $start_date->format(\DateTime::ATOM);
                
                // Get end of day
                $end_date = new \DateTime('yesterday');
                $end_date->setTimezone(new \DateTimeZone('Europe/London'));
                $end_date->setTime(23, 59, 59);
                $end_date_formatted = $end_date->format(\DateTime::ATOM);

                // Get end of day
                break;
            case 'custom':
                // Get start date from option
                $inputted_start = $input->getArgument('Start Date');

                $start_date_check = strtotime($inputted_start);

                if(!$start_date_check) {
                    $output->writeln('<error>Custom start date given in incorrect format. Please ensure it is in (dd/mm/yyyy).</error>');
                    return;
                }

                $start_date = new \DateTime();
                $start_date->setTimezone(new \DateTimeZone('Europe/London'));
                $start_date->setTimestamp($start_date_check);
                $start_date_formatted = $start_date->format(\DateTime::ATOM);

                // Get end date from option
                $inputted_end = $input->getArgument('End Date');
                $end_date_check = strtotime($inputted_end);

                if(!$end_date_check) {
                    $output->writeln('<error>Custom end date given in incorrect format. Please ensure it is in (dd/mm/yyyy).</error>');
                    return;
                }

                $end_date = new \DateTime();
                $end_date->setTimezone(new \DateTimeZone('Europe/London'));
                $end_date->setTimestamp($end_date_check);
                $end_date->setTime(23, 59, 59);
                $end_date_formatted = $end_date->format(\DateTime::ATOM);

                break;
            default:
                // Return an error invalid item given
                $output->writeln('<error>Invalid date type given. Please check that it is either of the following: (custom, today, yesterday).</error>');
                return;
                break;
        }

        // Ask which workspace you want to be in
        

        // In future we might not need this. We could just track if we are using an archived project and use it
        // However this could cause more work since some archived projects have the name.
        $question_helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Do you wish to also time track archived Projects? (y/n) ', false);
        $archived = true;

        if (!$question_helper->ask($input, $output, $question)) {
            $archived = false;
        }

        // Maybe map project ids from toggl to codebase projects

        $cb_helper = new CodebaseApiHelper();
        $projects = $cb_helper->projects($archived);

        $toggl_helper = new TogglApiHelper();
        $times = $toggl_helper->times($start_date_formatted, $end_date_formatted);
        $projects = '';

        $errors[] = array();

        // Take the times given and loop through them.
        foreach($times as $time_entry) {
            $project_id = $time_entry['pid'];

            $project = $toggl_helper->getProjectById($project_id);

            var_dump($project['name']);

            // var_dump($project['name']);

            // Get project for time entry.

        }

        // Based on this choose whether to take in start and end dates

        // Get all of our times based on input variables

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
