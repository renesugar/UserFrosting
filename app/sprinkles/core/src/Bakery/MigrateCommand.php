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
             ->addOption('database', 'd', InputOption::VALUE_REQUIRED, 'The database connection to use')
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
        $database = $input->getOption('database');

        /** @var UserFrosting\Sprinkle\Core\Database\Migrator */
        $migrator = $this->ci->migrator;

        // Set connection to the selected database
        if ($database != "") {
            $this->io->note("Running migrate command with `$database` database connection");
            $this->ci->db->getDatabaseManager()->setDefaultConnection($database);
        }

        // Make sure repository exist. Should be done in ServicesProvider,
        // but if we change connection, it might not exist
        if (!$migrator->repositoryExists()) {
            $migrator->getRepository()->createRepository();
        }

        // Show note if pretending
        if ($pretend) {
            $this->io->note("Running migration in pretend mode");
        }

        // Run migration
        $migrated = $migrator->run(['pretend' => $pretend, 'step' => $step]);

        // Get notes and display them
        $this->io->writeln($migrator->getNotes());

        // If all went well, there's no fatal errors and we have migrated
        // something, show some success
        if (empty($migrated)) {
            $this->io->success("Nothing to migrate");
        } else {
            $this->io->success("Migration successful !");
        }
    }
}
