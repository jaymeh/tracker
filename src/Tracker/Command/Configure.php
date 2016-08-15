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
    	$value = Yaml::parse(file_get_contents('/path/to/file.yml'));

    	var_dump(exec('whoami'));

		file_put_contents('/path/to/file.yml', $yaml);

    	// What do we need from the user?
    	// Codebase API User
    	// Codebase API Key
    	// Toggl API Key

    	$helper = $this->getHelper('question');
	    $question = new Question('Please enter your api username for Codebase (You can generate this username here <info>https://creode.codebasehq.com/settings/profile</info>): ', '');

	    $cb_api_user = $helper->ask($input, $output, $question);

	    $question2 = new Question('Please enter your api key for Codebase (You can generate this key here <info>https://creode.codebasehq.com/settings/profile</info>): ', '');

	    $cb_api_key = $helper->ask($input, $output, $question2);

	    $question3 = new Question('Please enter your api key for Toggl (You can find this at the following link <info>https://toggl.com/app/profile</info>): ', '');

	    $toggl_api_key = $helper->ask($input, $output, $question3);

	    $data = array(
		    'cb_api_user' => $cb_api_user,
		    'cb_api_key' => $cb_api_key,
		    'toggl_api_key' => $toggl_api_key,
		);

		$yaml = Yaml::dump($data);
    }
}
