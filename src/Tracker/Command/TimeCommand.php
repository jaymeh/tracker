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
use Tracker\Helper\FormatHelper;

class TimeCommand extends Command
{
    protected function configure()
    {
        // Setup the command arguments
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
        // Set default timezone so we don't have some weird time issues.
        date_default_timezone_set('Europe/London');

        // Figure out which date type we are using
        $date_type = $input->getArgument('Date Type');

        // Check the day
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
        
        // Force archived projects since if we have tracked time on it we should know
        $archived = true;

        // Load in projects
        $cb_helper = new CodebaseApiHelper();
        $projects = $cb_helper->projects($archived);

        // Setup placeholder for project data
        $cb_project_data = array();

        // Put data into another array in a format that helps us
        // reduce the api calls we are making
        foreach($projects as $cb_project) {
        	if(!isset($cb_project_data[$cb_project['name']])) {
        		$cb_project_data[$cb_project['name']] = $cb_project;
        	}
        }

        // Load in the toggl helper and get all time entries based
        // on the given dates
        $toggl_helper = new TogglApiHelper();
        $times = $toggl_helper->times($start_date_formatted, $end_date_formatted);

        $projects = '';
        $errors = array();
        $status = array();

        // Take the times given and loop through them.
        foreach($times as $time_entry) {
            if(!isset($time_entry['pid'])) {
                // No project. Skip this
                $errors[] = 'Could not find project attached to time entry: '.$time_entry['description'];
                continue;
            }

            $project = $toggl_helper->getProjectById($time_entry['pid']);

            if(!$project) {
                
            }

            // If we don't have a project item with name skip it.
            // Maybe in future we can email a report of this.
            if(!isset($cb_project_data[$project['name']])) {
                continue;
            }

            // Get project item based on the project from Toggl
            $cb_project_item = $cb_project_data[$project['name']];

            // Check for the touch with a ticket id and use it somehow.
            $note = $time_entry['description'];

            if($cb_project_item) {
            	// We have a match and time entry lets push them up :)
            	$project_link  = $cb_project_item['permalink'];

            	$start_date = new \DateTime($time_entry['start']);
            	$start_date->setTimeZone(new \DateTimeZone('Europe/London'));

                // Add to email report that we don't have stop time
                if(!isset($time_entry['stop'])) {
                    continue;
                }

            	$end_date = new \DateTime($time_entry['stop']);
            	$end_date->setTimeZone(new \DateTimeZone('Europe/London'));

            	$start_time = '';
            	$end_time = '';

            	$time = array('duration' => $time_entry['duration'], 'start' => $time_entry['start'], 'stop' => $time_entry['stop']);

                $ticket_string = $format_helper->get_string_between($note, '[', ']');

                $ticket_id = false;

                if($ticket_string !== false) {
                   $ticket_id = $cb_helper->checkTicketId($ticket_string); 
                }

                if($ticket_id) {
                    // Log the ticket
                   $server_response = $cb_helper->createTimeSession($project_link, $time, $note, $ticket_id);
                    continue;
                }

                // Log the time entry
                // Skip log entry
                $server_response = $cb_helper->createTimeSession($project_link, $time, $note);
            }
        }
    }
}
