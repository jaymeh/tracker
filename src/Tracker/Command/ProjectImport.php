<?php

namespace Tracker\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Tracker\Helper\CodebaseApiHelper;
use Tracker\Helper\FormatHelper;
use Tracker\Helper\TogglApiHelper;

class ProjectImport extends Command
{
    protected function configure()
    {
        $this
            ->setName('project-import')
            ->setDescription('Imports Projects from Codebase')
            ->addOption(
                'purge',
                'p',
                InputOption::VALUE_NONE,
                'Removes all current clients and projects before reimporting'
            );
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Load in toggl helper and bring in workspaces
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

        // Set the workspace id by default to the first workspace. This can change if we have more than one.
        $workspace_id = $workspaces[0]['id'];

        $purge = ($input->getOption('purge'));
        if($purge !== false)
        {
            $this->purgeClients($api_caller_toggl, $workspace_id);
        }

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

        $question = new ConfirmationQuestion('Do you wish to import archived Projects? (y/n) ', false);
        $archived = true;

        if (!$question_helper->ask($input, $output, $question)) {
            $archived = false;
        }

        // Use the workspace id to create clients and projects
        $cb_helper = new CodebaseApiHelper();
        $codebase_projects = $cb_helper->projects($archived);

        if(!is_array($codebase_projects)) {
          $output->writeln('<error>'.$codebase_projects.'</error>');
          return 500;
        }
        
        // Loop through the projects
        foreach($codebase_projects as $codebase_project) {
            $codebase_project = $codebase_project['project'];
            $client_data = $api_caller_toggl->createClient($codebase_project['name'], $workspace_id);

            if(isset($client_data['error_code'])) {
                $output->writeln('<error>('.$codebase_project['name'].'). '.$client_data['error_message'].'.</error>');

                // Bit of a pain but would be nice to be able to check and create a project for this if it doesn't exist
                // This will involve an extra api call in order to fix it.
            } else {
                // Check we have a project with that client. If not create it.
                $client_id = false;

                if(isset($client_data['data']['id'])) {
                    $client_id = $client_data['data']['id'];
                }

                $projects = $api_caller_toggl->getProjectByClient($client_id);

                if($projects == "") {
                  $projects = array();
                }

                if(!is_array($projects)) {
                  // If we have a string it must be an error throw it
                  $output->writeln('<error>'.$projects.'</error>');
                  return 500;
                }

                // Check if we have any projects with this client - We could
                // just ignore it here as API checks this. But maybe
                // in future we could check it exists anyway
                if(count($projects)) {
                    /* foreach($projects as $toggl_project) {
                
                    } */
                }

                // Create project with that client if all data is in order
                if(!count($projects) && $client_id !== false) {
                    // Create our project
                    $project_response = $api_caller_toggl->createProject($codebase_project['name'], $workspace_id, $client_id, 0);

                    if(isset($project_response['error_code'])) {
                        $output->writeln('<error>Couldn\'t create project: ('.$codebase_project['name'].'). '.$project_response['error_message'].'.</error>');
                    }
                }

                // Output to let us know things were successful
                $output->writeln('<info>Created a new Client and Project: '.$codebase_project['name'].'</info>');
            }
        }

        return;
    }

    private function purgeClients($toggl_helper, $workspace_id)
    {
        // Trigger deletion.
        
        $clients = $toggl_helper->getClientsByWorkspace($workspace_id);

        var_dump($clients);
        die;

        $client_count = count($clients);

        // foreach($clients as $client)
        // {
        //     $client_response = $toggl_helper->deleteClient($endpoint);

        //     var_dump($client_response);
        // }
        
        https://www.toggl.com/api/v8/workspaces/{workspace_id}/clients
        
        // Get toggl api results for projects and clients.
    }
}
