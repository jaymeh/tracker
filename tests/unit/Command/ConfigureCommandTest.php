<?php

namespace Tracker\Tests\Unit;

use Tracker\Command\Configure;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../bootstrap.php';

class ConfigureCommandTest extends TestCase
{
    /**
     * @test
     */
    public function testExecute()
    {
        $application = new Application();

        $application->add(new Configure());

        $command = $application->find('configure');
        $commandTester = new CommandTester($command);

        // Set codebase user
        $commandTester->setInputs(array('Test', 'test', 'test', 'test'));
        $commandTester->execute(array('command' => $command->getName()));

        // // Set codebase api key
        // $commandTester->setInputs(array('Test'));

        // // Set toggl api key
        // $commandTester->setInputs(array('Test'));

        // // Set Workspace
        // $commandTester->setInputs(array('Test'));


        $output = $commandTester->getDisplay();
        var_dump($output);
    }
}