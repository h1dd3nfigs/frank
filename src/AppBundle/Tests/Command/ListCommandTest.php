<?php

use AppBundle\Command\GreetCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ListCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testExecute()
    {
        $application = new Application();
        $application->add(new GreetCommand());

        $command = $application->find('demo:greet');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'      => $command->getName(),
            'name'         => 'ruth',
            // '--iterations' => 5,
        ));

        $this->assertRegExp('/ruth/', $commandTester->getDisplay());

        // ...
    }
}