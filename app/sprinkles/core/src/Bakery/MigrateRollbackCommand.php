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
use UserFrosting\System\Bakery\Migrator;

/**
 * Migrate CLI Tools.
 * Perform database migrations commands
 *
 * @author Alex Weissman (https://alexanderweissman.com)
 */
class MigrateRollbackCommand extends MigrateCommand
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName("migrate:rollback")
             ->setDescription("Rollback last database migration")
             ->addOption('steps', 's', InputOption::VALUE_REQUIRED, 'Number of batch to rollback', 1)
             ->addOption('database', 'd', InputOption::VALUE_REQUIRED, 'The database connection to use')
             ->addOption('pretend', 'p', InputOption::VALUE_NONE, 'Run migrations in "dry run" mode');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io->title("Migration rollback");

        $steps = $input->getOption('steps');
        $pretend = $input->getOption('pretend');

        // Rollback migrations
        $migrator = $this->setupMigrator($input);
        $migrated = $migrator->rollback(['pretend' => $pretend, 'steps' => $steps]);

        // Get notes and display them
        $this->io->writeln($migrator->getNotes());

        // If all went well, there's no fatal errors and we have migrated
        // something, show some success
        if (empty($migrated)) {
            $this->io->success("Nothing to rollback");
        } else {
            $this->io->success("Rollback successful !");
        }
    }
}
