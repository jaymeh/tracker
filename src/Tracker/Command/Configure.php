<?php

namespace Tracker\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Question\Question;

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

    	$check_file = fopen($file, "w");

    	// What do we need from the user?
    	// Codebase API User
    	// Codebase API Key
    	// Toggl API Key

    	$helper = $this->getHelper('question');
	    $question = new Question('Please enter your api username for Codebase (You can generate this username here <info>https://creode.codebasehq.com/settings/profile</info>): ', '');

	    $cb_api_user = $helper->ask($input, $output, $question);

	    if($cb_api_user == '') {
	    	$output->writeln('<error>Couldn\'t find api user entry. Please check it and try again.</error>');
	    	return;
	    }

	    $question2 = new Question('Please enter your api key for Codebase (You can generate this key here <info>https://creode.codebasehq.com/settings/profile</info>): ', '');

	    $cb_api_key = $helper->ask($input, $output, $question2);

	    if($cb_api_key == '') {
	    	$output->writeln('<error>Couldn\'t find api key entry. Please check it and try again.</error>');
	    	return;
	    }

	    $question3 = new Question('Please enter your api key for Toggl (You can find this at the following link <info>https://toggl.com/app/profile</info>): ', '');

	    $toggl_api_key = $helper->ask($input, $output, $question3);

	    if($toggl_api_key == '') {
	    	$output->writeln('<error>Couldn\'t find toggl api key. Please check it and try again.</error>');
	    	return;
	    }

	    $data = array(
		    'cb_api_user' => trim($cb_api_user),
		    'cb_api_key' => trim($cb_api_key),
		    'toggl_api_key' => trim($toggl_api_key),
		);

		$yaml = Yaml::dump($data);

		if(file_put_contents($file, $yaml)) {
			$output->writeln('<info>Successfully created configuration file at: '.$file.'</info>');
			return 1;
		}

		$output->writeln('<error>Couldn\'t create file at: '.$file.'</info>');
    }
}
