<?php

namespace Tracker\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;

use Symfony\Component\Console\Input\InputArgument;

use Tracker\Helper\CodebaseApiHelper;
use Tracker\Helper\TogglApiHelper;
use Tracker\Helper\FormatHelper;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

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
                break;
            case 'custom':
                // Get start date from option
                $inputted_start = $input->getArgument('Start Date');
                $inputted_start = str_replace('/', '-', $inputted_start);

                $start_date_check = strtotime($inputted_start);

                // Check that we have a valid start date
                if(!$start_date_check) {
                    $output->writeln('<error>Custom start date given in incorrect format. Please ensure it is in (dd/mm/yyyy).</error>');
                    return;
                }

                // Format the date how we need it for the api call
                $start_date = new \DateTime();
                $start_date->setTimezone(new \DateTimeZone('Europe/London'));
                $start_date->setTimestamp($start_date_check);
                $start_date_formatted = $start_date->format(\DateTime::ATOM);

                // Get end date from option
                $inputted_end = $input->getArgument('End Date');
                $inputted_end = str_replace('/', '-', $inputted_end);
                $end_date_check = strtotime($inputted_end);

                // Check that we have a valid end date
                if(!$end_date_check) {
                    $output->writeln('<error>Custom end date given in incorrect format. Please ensure it is in (dd/mm/yyyy).</error>');
                    return;
                }

                // Format the date how we need it for the api call
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
        $config_data = $cb_helper->get_config_data();

        if($config_data !== true) {
        	$output->writeln('<error>'.$config_data.'</error>');
        	return;
        }


        $projects = $cb_helper->projects($archived);

        if(!is_array($projects)) {
            if($projects == 'HTTP Basic: Access denied.') {
                $output->writeln('<error>Invalid API credentials provided. Please check them in your config file or re-run configure command.</error>');
                return 500;
            } else {
                $output->writeln('<error>'.$projects.'</error>');
                return 500;
            }
        }

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
        $toggl_config_data = $toggl_helper->get_config_data();

        if($toggl_config_data !== true) {
        	$output->writeln('<error>'.$toggl_config_data.'</error>');
        	return;
        }

        $times = $toggl_helper->times($start_date_formatted, $end_date_formatted);

        $projects = '';
        $errors = array();
        $status = array();

        /* Combine time entries based on project, date and description */
        $times_temp = $times;

        foreach($times as $key => $time_entry) {
            // Setup current times variables
            $id = $time_entry['id'];
            $start_date_timestamp = strtotime($time_entry['start']);
            $start_formatted = date('d/m/Y', $start_date_timestamp);

            $end_date_timestamp = strtotime($time_entry['stop']);
            $end_formatted = date('d/m/Y', $end_date_timestamp);

            $description = $time_entry['description'];
            $project = $time_entry['pid'];

            // Loop through each time entry checking for the duplicates
            foreach($times_temp as $new_key => $time_new) {
                if($id !== $time_new['id']) {
                    $time_new_start_timestamp = strtotime($time_new['start']);
                    $time_new_start_formatted = date('d/m/Y', $time_new_start_timestamp);

                    $time_new_end_timestamp = strtotime($time_new['start']);
                    $time_new_end_formatted = date('d/m/Y', $time_new_end_timestamp);
                    
                    // Check if the project, start date, end date and description match each other. If they do then condense the entry down
                    if($time_new_start_formatted == $start_formatted &&
                        $time_new_end_formatted == $end_formatted &&
                        $description == $time_new['description'] &&
                        $project == $time_new['pid']) {

                        if($times[$key]) {
                            $times[$key]['duration'] += $time_new['duration'];
                            unset($times[$new_key]);
                        }
                    }
                }
            }
        }

        // Take the times given and loop through them.
        foreach($times as $time_entry) {
            if(!isset($time_entry['pid'])) {
                // Can't find the project from toggl
                $errors[] = 'Could not find toggl project attached to time entry: '.$time_entry['description'];
                continue;
            }

            $project = $toggl_helper->getProjectById($time_entry['pid']);

            if(!$project) {
                // Report project error
                $errors[] = 'Could not find codebase project attached to time entry: '.$time_entry['description'];
                continue;
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

                // Add to email report that we don't have stop time
                if(!isset($time_entry['stop'])) {
                    continue;
                }

                // Setup time
            	$time = array('duration' => $time_entry['duration'], 'start' => $time_entry['start'], 'stop' => $time_entry['stop']);

                // Try to get a ticket id from the time entry
                $format_helper = new FormatHelper();
                $ticket_string = $format_helper->get_string_between($note, '[', ']');

                $ticket_id = false;

                if($ticket_string !== false) {
                   $ticket_id = $cb_helper->checkTicketId($ticket_string); 
                }

                // Get the duration to output. This is because toggl returns it
                // in seconds so we convert it to minutes.
                $duration = $time['duration'] / 60;

                if($ticket_id) {
                    // Log the ticket
                   $server_response = $cb_helper->createTimeSession($project_link, $time, $note, $ticket_id);

                   // Strip out the touch for the description in future
                   $stripped_duration = $format_helper->delete_all_between($note, '[', ']');

                   // Output something to help see whats happening
                   $output->writeln('<info>Tracked Time entry to Codebase Ticket ('.$ticket_id.'): "'.trim($stripped_duration).'" '.intval(round($duration)).' minutes</info>');
                    continue;
                }

                // Log the time entry
                $server_response = $cb_helper->createTimeSession($project_link, $time, $note);

                // Output something to help see whats happening
                $output->writeln('<info>Tracked Time entry to Codebase: "'.$time_entry['description'].'" '.intval(round($duration)).' minutes</info>');
            }
        }
    }
}
