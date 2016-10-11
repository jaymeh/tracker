<?php

namespace Tracker\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;

use Tracker\Helper\CodebaseApiHelper;
use Tracker\Helper\TogglApiHelper;
use Tracker\Helper\FormatHelper;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class Configure extends Command
{
    protected function configure()
    {
        // Setup the command arguments
        $this
            ->setName('configure')
            ->setDescription('Configures this tool to use with various APIs')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
    	// $value = Yaml::parse(file_get_contents('/path/to/file.yml'));

    	$user = exec('whoami');

    	$directory = '/Users/'.$user.'/.tracker/';

    	if(!file_exists($directory)) {
    		// Try to create directory
	    	$directory_create = mkdir($directory, 0777, true);

	    	if(!$directory_create) {
	    		$output->writeln('<error>Failed to create directory inside: '.$directory.'. Please check this is writeable by: '.$user.'</error>');
	    		return;
	    	}
    	}

    	$file = $directory.'config.yml';

    	$codebase_api_user = '';
    	$codebase_api_key = '';
    	$toggl_api_key = '';

    	if(file_exists($file)) {
    		$toggl_helper = new TogglApiHelper();
    		$cb_helper = new CodebaseApiHelper();
			$codebase_api_user = $cb_helper->api_user;
			$codebase_api_key = $cb_helper->api_key;
    		$toggl_api_key = $toggl_helper->api_key;
    	}

    	// What do we need from the user?
    	// Codebase API User
    	// Codebase API Key
    	// Toggl API Key

    	$helper = $this->getHelper('question');
	    $question = new Question('Please enter your api username for Codebase ( You can generate this username here <info>https://creode.codebasehq.com/settings/profile</info> ): ', $codebase_api_user);

	    $cb_api_user = $helper->ask($input, $output, $question);

	    if($cb_api_user == '') {
	    	$output->writeln('<error>Couldn\'t find api user entry. Please check it and try again.</error>');
	    	return;
	    }

	    $question2 = new Question('Please enter your api key for Codebase ( You can generate this key here <info>https://creode.codebasehq.com/settings/profile</info> ): ', $codebase_api_key);

	    $cb_api_key = $helper->ask($input, $output, $question2);

	    if($cb_api_key == '') {
	    	$output->writeln('<error>Couldn\'t find api key entry. Please check it and try again.</error>');
	    	return;
	    }

	    $question3 = new Question('Please enter your api key for Toggl ( You can find this at the following link <info>https://toggl.com/app/profile</info> ): ', $toggl_api_key);

	    $toggl_api_key = $helper->ask($input, $output, $question3);

	    if($toggl_api_key == '') {
	    	$output->writeln('<error>Couldn\'t find toggl api key. Please check it and try again.</error>');
	    	return;
	    }

	    $api_caller_toggl = new TogglApiHelper();
        $workspaces = $api_caller_toggl->workspaces();

        // If we have a string it must be an error throw it
        if(!is_array($workspaces)) {
          $output->writeln('<error>'.$workspaces.'</error>');
          return 500;
        }

        // If we don't have any throw an error
        if(empty($workspaces)) {
        	$output->writeln('<error>Couldn\'t find a workspace to use when importing projects. Please check your toggl api key is correct.</error>');
        	return 404;
        }

        if(isset($api_caller_toggl->workspace_id) && $api_caller_toggl->workspace_id) {
        	$toggl_workspace_id = $api_caller_toggl->workspace_id;

        	$question_helper = $this->getHelper('question');
        } else {
        	// Set the workspace id by default to the first workspace. This can change if we have more than one.
	        $toggl_workspace_id = $workspaces[0]['id'];

	        $question_helper = $this->getHelper('question');
        }

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
        		'0'
        	);

        	// Set a custom error message
        	$question->setErrorMessage('Workspace %s is invalid.');

        	// Ask the question
        	$option_selection = $question_helper->ask($input, $output, $question);

        	$output->writeln('<info>Selected the '.$option_selection.' workspace</info>');

        	// Set the id
          	$format_helper = new FormatHelper();
        	$toggl_workspace_id = $format_helper->get_string_between($option_selection);

        	// Check the id and if there isn't one then set an error
        	if($toggl_workspace_id == false) {
        		$question->setErrorMessage('Workspace %s does not contain an id.');
        	}
        }

	    $data = array(
		    'cb_api_user' => trim($cb_api_user),
		    'cb_api_key' => trim($cb_api_key),
		    'toggl_api_key' => trim($toggl_api_key),
		    'toggl_workspace_id' => trim(intval($toggl_workspace_id))
		);

		$yaml = Yaml::dump($data);

		if(file_put_contents($file, $yaml)) {
			$output->writeln('<info>Successfully created configuration file at: '.$file.'</info>');
			return 1;
		}

		$output->writeln('<error>Couldn\'t create file at: '.$file.'</info>');
    }
}
