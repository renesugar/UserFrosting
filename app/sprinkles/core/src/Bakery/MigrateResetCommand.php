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
use UserFrosting\Sprinkle\Core\Bakery\MigrateCommand;

/**
 * migrate:reset Bakery Command
 * Reset the database to a clean state
 *
 * @author Louis Charette
 */
class MigrateResetCommand extends MigrateCommand
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName("migrate:reset")
             ->setDescription("Reset the whole database to an empty state, rolling back all migrations.")
             ->addOption('pretend', 'p', InputOption::VALUE_NONE, 'Run migrations in "dry run" mode.')
             ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force the operation to run when in production.')
             ->addOption('database', 'd', InputOption::VALUE_REQUIRED, 'The database connection to use.');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io->title("Migration reset");

        // Get options
        $pretend = $input->getOption('pretend');

        // Reset migrator
        $migrator = $this->setupMigrator($input);
        $resetted = $migrator->reset($pretend);

        // Get notes and display them
        $this->io->writeln($migrator->getNotes());

        // Delete the repository
        if (!$pretend && $migrator->repositoryExists()) {
            $this->io->writeln("<info>Deleting migration repository</info>");
            $migrator->getRepository()->deleteRepository();
        }

        // If all went well, there's no fatal errors and we have migrated
        // something, show some success
        if (empty($resetted)) {
            $this->io->success("Nothing to reset");
        } else {
            $this->io->success("Reset successful !");
        }
    }
}
