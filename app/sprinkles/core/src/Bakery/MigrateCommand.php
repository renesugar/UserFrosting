<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UserFrosting
 * @license   https://github.com/userfrosting/UserFrosting/blob/master/licenses/UserFrosting.md (MIT License)
 */
namespace UserFrosting\Sprinkle\Core\Bakery;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use UserFrosting\System\Bakery\BaseCommand;

/**
 * Migrate CLI Tools.
 * Perform database migrations commands
 *
 * @author Louis Charette
 */
class MigrateCommand extends BaseCommand
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName("migrate")
             ->setDescription("Perform database migration")
             ->setHelp("This command runs all the pending database migrations.")
             ->addOption('pretend', 'p', InputOption::VALUE_NONE, 'Run migrations in "dry run" mode')
             ->addOption('step', 's', InputOption::VALUE_NONE, 'Force the migrations to be run so they can be rolled back individually.');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io->title("UserFrosting's Migrator");

        // Get options
        $pretend = $input->getOption('pretend');
        $step = $input->getOption('step');

        /** @var UserFrosting\Sprinkle\Core\Database\Migrator */
        $migrator = $this->ci->migrator;

        // Set connection to the selected database

        }

        // Run migration
        $migrator->run(['pretend' => $pretend, 'step' => $step]);

        // Get notes and display them
        $this->io->writeln($migrator->getNotes());
    }
}
