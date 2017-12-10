<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UserFrosting
 * @license   https://github.com/userfrosting/UserFrosting/blob/master/licenses/UserFrosting.md (MIT License)
 */
namespace UserFrosting\Tests\Integration;

use Mockery as m;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use UserFrosting\Sprinkle\Core\Bakery\MigrateCommand;
use UserFrosting\Tests\TestCase;

class DatabaseMigrationMigrateCommandTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testBasicMigrationsCallMigratorWithProperArguments()
    {
        // Setup migrator mock
        $migrator = m::mock('UserFrosting\Sprinkle\Core\Database\Migrator');
        $migrator->shouldReceive('run')->once()->with(['pretend' => false, 'step' => false])->andReturn([]);
        $migrator->shouldReceive('getNotes')->andReturn(["Nothing to Migrate"]);

        // Run command
        $commandTester = $this->runCommand($migrator, []);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertContains('Nothing to Migrate', $output);
    }

    protected function runCommand($migrator, $input = [])
    {
        // Place the mock migrator inside the $ci
        $ci = $this->ci;
        $ci->migrator = $migrator;

        // Create the app, create the command, replace $ci and add the command to the app
        $app = new Application();
        $command = new MigrateCommand();
        $command->setContainer($ci);
        $app->add($command);

        // Add the command to the input to create the execute argument
        $execute = array_merge([
            'command' => $command->getName()
        ], $input);

        // Execute command tester
        $commandTester = new CommandTester($command);
        $commandTester->execute($execute);

        return $commandTester;
    }
}
