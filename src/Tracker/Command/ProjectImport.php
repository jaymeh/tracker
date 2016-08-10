<?php

namespace Tracker\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Question\ChoiceQuestion;

use Tracker\Helper\CodebaseApiHelper;
use Tracker\Helper\TogglApiHelper;


class ProjectImport extends Command
{
    protected function configure()
    {
        $this
            ->setName('project-import')
            ->setDescription('Imports Projects from Codebase')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
    	// Load in toggl helper and bring in workspaces
        $api_caller_toggl = new TogglApiHelper();
        $workspaces = $api_caller_toggl->workspaces();

        // If we don't have any throw an error
        if(empty($workspaces)) {
        	$output->writeln('<error>Couldn\'t find a workspace to use when importing projects. Please check your toggl api key is correct.</error>');
        	return 404;
        }

        // Set the workspace id by default to the first workspace. This can change if we have more than one.
        $workspace_id = $workspaces[0]['id'];

        // Check for the creode workspace
        if(count($workspaces) > 1) {
        	$workspace_names = array();

        	// Loop through workspaces to map what we want.
        	foreach($workspaces as $workspace) {
        		$workspace_names[] = $workspace['name'].' {'.$workspace['id'].'}';
        	}

        	// Allow user to pick which workspace they want to use
        	$question_helper = $this->getHelper('question');
        	$question = new ChoiceQuestion(
        		'Please select which project to use',
        		$workspace_names,
        		'0,1'
        	);

        	// Set a custom error message
        	$question->setErrorMessage('Workspace %s is invalid.');

        	// Ask the question
        	$option_selection = $question_helper->ask($input, $output, $question);

        	// Set the id
        	$workspace_id = $this->get_string_between($option_selection);

        	// Check the id and if there isn't one then set an error
        	if($workspace_id == false) {
        		$question->setErrorMessage('Workspace %s does not contain an id.');
        	}
        }

        // Use the workspace id to create clients and projects
        $cb_helper = new CodebaseApiHelper();
       	$codebase_projects = $cb_helper->projects();
       	
       	// Loop through the projects
       	foreach($codebase_projects['project'] as $codebase_project) {
       		$api_caller_toggl->createClient($codebase_project['name'], $workspace_id);
       		die;
       	}

        // $output->writeln('Hello World');
    }

    /* Not required now but may be used in future to create a new workspace
    private function registerWorkspace() {

    } */

    private function get_string_between($string, $start = '{', $end = '}'){
	    $string = ' ' . $string;
	    $ini = strpos($string, $start);
	    if ($ini == 0) { return false; }
	    $ini += strlen($start);
	    $len = strpos($string, $end, $ini) - $ini;
	    return substr($string, $ini, $len);
	}
}
