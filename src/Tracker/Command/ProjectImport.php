<?php

namespace Tracker\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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
    	/*$api_caller = new CodebaseApiHelper();

        $projects = $api_caller->projects();

        var_dump($projects); */

        $api_caller_toggl = new TogglApiHelper();
        $workspaces = $api_caller_toggl->workspaces();

        if(empty($workspaces)) {
        	$output->writeln('<error>Couldn\'t find a workspace to use when importing projects. Please check your toggl api key is correct.</error>');
        	return 404;
        }

        $workspace_id = 0;

        $workspace_id = $workspaces[0]['id'];
       	$output->writeln($workspace_id);

        if(count($workspaces > 1)) {
        	// Output a message for which workspace to use and set its id
        }
        // $output->writeln('Hello World');
    }

}
