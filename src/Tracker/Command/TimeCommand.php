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

        // Load in the Toggl Helper
        $toggl_helper = new TogglApiHelper();

        // Get the workspace id
        $workspace_id = $this->getWorkspace($toggl_helper, $input, $output);
        if($workspace_id == false) {
            return $output->writeln('<error>Couldn\'t determine workspace id</error>');
        }

        $inputted_dates = $this->checkDateType($date_type);

        // If we can't get any data from the type we have then throw an error.
        if(!$inputted_dates) {
            return $output->writeln('<error>Couldn\'t determine date type. Please check and try again.</error>');
        }

        // We expect it to be an array if it isn't then throw an error.
        if(!is_array($inputted_dates)) {
            return $output->writeln('<error>Couldn\'t determine the format of inputted dates. Please check and try again.</error>');
        }

        // Setup Start Date Variables
        $start_date = $inputted_dates['start_date'];
        $start_date_formatted = $inputted_dates['start_date_formatted'];

        // Setup End Date Variables
        $end_date = $inputted_dates['end_date'];
        $end_date_formatted = $inputted_dates['end_date_formatted'];
        
        // Force archived projects since if we have tracked time on it we should always know
        $archived = true;

        // Load in projects
        $cb_helper = new CodebaseApiHelper();
        $config_data = $cb_helper->getConfigData();

        // Error
        if($config_data !== true) {
        	$output->writeln('<error>'.$config_data.'</error>');
        	return;
        }

        // Add some nice debugging to a user to say we are still runnning the program
        $output->writeln('<fg=white;bg=black>Finding codebase projects.</>');
        $projects = $cb_helper->projects($archived);

        // Checks on the api for valid credentials
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
            $cb_project = $cb_project['project'];

        	if(!isset($cb_project_data[$cb_project['name']])) {
        		$cb_project_data[$cb_project['name']] = $cb_project;
        	}
        }

        // Get all time entries based on the given dates
        $toggl_config_data = $toggl_helper->getConfigData();

        if($toggl_config_data !== true) {
        	$output->writeln('<error>'.$toggl_config_data.'</error>');
        	return;
        }

        // Add another nice message to show the user we are still processing
        $output->writeln('<fg=white;bg=black>Pulling in times from Toggl and correctly formatting the results.</>');

        // Grab times from toggl
        $times = $toggl_helper->times($start_date_formatted, $end_date_formatted, $workspace_id);

        // Format the times array to how we want it
        $times = $this->formatTogglTimes($times, $toggl_helper, $cb_project_data);

        // Let user know what we are doing
        $output->writeln('<fg=white;bg=black>Grabbing required projects for current toggle time range.</>');

        // Load in toggl time session
        $logged_times = $this->loadCodebaseSessions($times, $toggl_helper, $cb_helper, $cb_project_data, $start_date, $end_date);

        // Let user know we are processing time entries
        $output->writeln('<fg=white;bg=black>Processing time entries.</>');
        $total_tracked_minutes = 0;

        // TODO: Show a counter/progress bar.

        /* SPLIT ME INTO MULTIPLE FUNCTIONS TO MAKE ME EASIER TO READ... PLEASE!!!! */

        // Take the times given and loop through them.
        foreach($times as $time_entry) {
            // Load in the toggl project data
            $project = $toggl_helper->getProjectById($time_entry['pid']);

            // Wrap basic validation in here
            if(!$this->validateTimeEntry($project, $cb_project_data, $time_entry, $output)) {
                continue;
            }

            // Get project item based on the project from Toggl
            $cb_project_item = $cb_project_data[$project['name']];

            // Setup description for time entry as note
            $note = $time_entry['description'];

            if($cb_project_item) {
            	// We have a match and time entry lets prepare to push it up/.
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

                // Check for the touch with a ticket id and use it somehow.
                $ticket_id = false;
                if($ticket_string !== false) {
                   $ticket_id = $cb_helper->checkTicketId($ticket_string);
                }

                // Get the duration to output. This is because toggl returns it
                // in seconds so we convert it to minutes.
                $duration = $time['duration'] / 60;

                if($duration < 0) {
                    $output->writeln('<error>Invalid duration found for time entry: '.$time_entry['description'].'. It may be because this entry is still tracking in Toggl. Please check this and try again.</error>');
                    continue;
                }

                if($ticket_id) {
                    /* If its a ticket then check that the date, id and project match */
                    $codebase_times = $logged_times[$cb_project_item['name']];

                    $matches = array();

                    $session_timestamp = strtotime($time['start']);
                    $session_date = date('Y-m-d', $session_timestamp);

                    $duplicate = false;

                    $stripped_duration = $format_helper->delete_all_between($note, '[', ']');

                    foreach($codebase_times as $codebase_time) {
                        if($codebase_time['ticket_id'] == $ticket_id &&
                            $codebase_time['session_date'] == $session_date &&
                            $codebase_time['minutes'] == intval(round($duration))) {
                            // Skip this because its already logged?
                            $output->writeln('<comment>Duplicate entry found for ('.$cb_project_item['name'].': Ticket '.$ticket_id.') - "'.trim($stripped_duration).'" '.intval(round($duration)).' minutes. Skipping this.</comment>');
                            $duplicate = true;
                        }
                    }

                    if($duplicate == false) {
                        // Log the ticket
                        $server_response = $cb_helper->createTimeSession($project_link, $time, $note, $ticket_id);

                        if($server_response !== true) {
                            $output->writeln('<error>'.$server_response.'</error>');
                            continue;
                        }

                        // Strip out the touch for the description in future
                        $stripped_duration = $format_helper->delete_all_between($note, '[', ']');

                        // Add total tracked mins to project
                        $total_tracked_minutes += intval(round($duration));

                        // Output something to help see whats happening
                        $output->writeln('<info>Tracked Time entry to ('.$cb_project_item['name'].': Ticket '.$ticket_id.') - "'.trim($stripped_duration).'" '.intval(round($duration)).' minutes</info>');
                    }                    
                    
                    continue;
                }

                /* Check that the time entry doesn't already exist */
                $codebase_times = $logged_times[$cb_project_item['name']];

                $matches = array();

                $session_timestamp = strtotime($time['start']);
                $session_date = date('Y-m-d', $session_timestamp);

                $duplicate = false;

                foreach($codebase_times as $codebase_time) {
                    if($codebase_time['summary'] == $time_entry['description'] &&
                        $codebase_time['session_date'] == $session_date &&
                        ($codebase_time['minutes']) == intval(round($duration))) {
                        // Skip this because its already logged?
                        $output->writeln('<comment>Duplicate entry found for ('.$cb_project_item['name'].') - "'.$time_entry['description'].'" '.intval(round($duration)).' minutes. Skipping this.</comment>');
                        $duplicate = true;
                    }
                }

                if(!$duplicate) {
                    // Log the time entry
                    $server_response = $cb_helper->createTimeSession($project_link, $time, $note);

                    // Improve Error Reporting
                    if($server_response !== true) {
                        $output->writeln('<error>'.$server_response.'</error>');
                        continue;
                    }

                    // Add total tracked time to duration
                    $total_tracked_minutes += intval(round($duration));

                    // Output something to help see whats happening
                    $output->writeln('<info>Tracked Time entry to ('.$cb_project_item['name'].') - "'.$time_entry['description'].'" '.intval(round($duration)).' minutes</info>');
                    
                }
            }
        }

        // Report total tracked time
        if($total_tracked_minutes !== 0) {
            $formatted_minutes = $format_helper->convert_codebase_minutes($total_tracked_minutes);
            $output->writeln('<fg=white;bg=black>You have tracked a total of '.$formatted_minutes.'.</>');
        }

    }

    private function getWorkspace($toggl_helper, &$input, &$output) {
        // Check workspace id.
        if(isset($toggl_helper->workspace_id) && $toggl_helper->workspace_id) {
            $workspace_id = $toggl_helper->workspace_id;
        } else {
            // Check which workspace to use.
            $workspaces = $toggl_helper->workspaces();

            // If we have a string it must be an error throw it
            if(!is_array($workspaces)) {
              $output->writeln('<error>'.$workspaces.'</error>');
              return false;
            }

            // If we don't have any throw an error
            if(empty($workspaces)) {
                $output->writeln('<error>Couldn\'t find a workspace to use when importing projects. Please check your toggl api key is correct.</error>');
                return false;
            }

            // Set the workspace id by default to the first workspace. This can change if we have more than one.
            $workspace_id = $workspaces[0]['id'];

            $question_helper = $this->getHelper('question');

            // Check for the creode workspace
            if(count($workspaces) > 1) {
                $workspace_names = array();

                // Loop through workspaces to map what we want.
                foreach($workspaces as $workspace) {
                    $workspace_names[] = $workspace['name'].' {'.$workspace['id'].'}';
                }

                // Allow user to pick which workspace they want to use
                $question = new ChoiceQuestion(
                    'Please select which project to use',
                    $workspace_names,
                    '0,1'
                );

                // Set a custom error message
                $question->setErrorMessage('Workspace %s is invalid.');

                // Ask the question
                $option_selection = $question_helper->ask($input, $output, $question);

                $output->writeln('<info>Selected the '.$option_selection.' workspace</info>');

                // Set the id
                $format_helper = new FormatHelper();
                $workspace_id = $format_helper->get_string_between($option_selection);

                // Check the id and if there isn't one then set an error
                if($workspace_id == false) {
                    $question->setErrorMessage('Workspace %s does not contain an id.');
                }
            }
        }

        // Convert to workspace id. Also could be in above function
        $workspace_id = intval($workspace_id);

        return $workspace_id;
    }

    private function checkDateType($date_type) {
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
                    return false;
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
                    return false;
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
                return false;
        }

        // Setup variables here? Pass them in?
        return array(
            'start_date' => $start_date,
            'start_date_formatted' => $start_date_formatted,
            'end_date' => $end_date,
            'end_date_formatted' => $end_date_formatted
        );
    }

    /**
     * Helper function to just format times from toggl a little nicer to make it easier for our checks
     * @param  array $times Array of times from toggl
     * @return array        Finalised array of times from toggl
     */
    private function formatTogglTimes($times) {
        $projects = '';
        $errors = array();
        $status = array();

        /* Combine time entries based on project, date and description */
        $times_temp = $times;

        // Format all the times how we want them
        foreach($times as $key => $time_entry) {
            // Setup current times variables
            $id = $time_entry['id'];
            $start_date_timestamp = strtotime($time_entry['start']);
            $start_formatted = date('d/m/Y', $start_date_timestamp);

            $end_date_timestamp = strtotime($time_entry['stop']);
            $end_formatted = date('d/m/Y', $end_date_timestamp);

            $description = $time_entry['description'];

            if(isset($time_entry['pid'])) {
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

                            if(isset($times[$key])) {
                                $times[$key]['duration'] += $time_new['duration'];
                                unset($times[$new_key]);
                            }
                        }
                    }
                }
            }
        }

        return $times;
    }

    /**
     * Generate an array of items to check if we have already logged the times given in codebase
     * @return array An Array grouped by project of time entries
     */
    private function loadCodebaseSessions($times, $toggl_helper, $cb_helper, $cb_project_data, $start_date, $end_date) {
        $logged_times = array();

        // Loop through the time entries and populate the times from our projects
        foreach($times as $time_entry) {
            if(!isset($time_entry['pid'])) {
                // Can't find the toggl project so move on
                continue;
            }
            $project = $toggl_helper->getProjectById($time_entry['pid']);

            if(isset($project['name'])) {
                if(isset($cb_project_data[$project['name']])) {
                    $cb_project = $cb_project_data[$project['name']];

                    $logged_times[$project['name']] = $cb_helper->getTimeSessions($cb_project['permalink'], $start_date, $end_date);
                }
            }
        }

        return $logged_times;
    }

    private function validateTimeEntry($project, $cb_project_data, $time_entry, &$output) {
        if(!$project) {
            // Report project error
            $output->writeln('<error>Could not find codebase project attached to time entry: '.$time_entry['description'].'</error>');
            return false;
        }

        // If we don't have a project item with name skip it.
        // Maybe in future we can email a report of this.
        if(!isset($cb_project_data[$project['name']])) {
            $output->writeln('<comment>Could not find project data for time entry: '.$time_entry['description'].'</comment>');
            return false;
        }

        // If we don't have a description for time entry then log an error
        if(!$time_entry['description']) {
            // Report project error
            $output->writeln('<error>Could not find description for time entry on project: '.$project['name'].'</error>');
            return false;
        }

        return true;
    }
}
