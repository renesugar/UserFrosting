<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UserFrosting
 * @license   https://github.com/userfrosting/UserFrosting/blob/master/licenses/UserFrosting.md (MIT License)
 */
namespace UserFrosting\Sprinkle\Core\Database;

use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Database\Schema\Builder;
use UserFrosting\Sprinkle\Core\Database\MigrationLocatorInterface;

/**
 * Migrator Class
 *
 * Migrator service used to manage and run database migrations
 *
 * @author Louis Charette
 */
class Migrator
{
    /**
     * The migration repository implementation.
     *
     * @var \UserFrosting\Sprinkle\Core\Database\MigrationLocatorInterface
     */
    protected $repository;

    /**
     * The schema builder instance
     *
     * @var \Illuminate\Database\Schema\Builder
     */
    protected $schema;

    /**
     * @var MigrationLocatorInterface The Migration locator instance
     */
    protected $locator;

    /**
     * @var Array The notes for the current operation.
     */
    protected $notes = [];

    /**
     *    Constructor
     *
     *    @param MigrationRepositoryInterface $repository The migration repository
     *    @param Builder $schema The schema builder
     *    @param MigrationLocatorInterface $locator The Migration locator
     */
    public function __construct(MigrationRepositoryInterface $repository, Builder $schema, MigrationLocatorInterface $locator)
    {
        $this->repository = $repository;
        $this->schema = $schema;
        $this->locator = $locator;
    }

    /**
     *    Run all the specified migrations up. Check that dependencies are met before running
     *
     *    @param  array $options Options for the current operations
     *    @return array The list of ran migrations
     */
    public function run(array $options = [])
    {
        $this->notes = [];

        // Get the list of available migration classes in the the Filesystem
        $availableMigrations = $this->getAvailableMigrations();

        // Get outstanding migrations classes that requires to be run up
        $pendingMigrations = $this->pendingMigrations($availableMigrations, $this->repository->getRan());

        // Run pending migration up
        $this->runPending($pendingMigrations, $options);

        return $pendingMigrations;
    }

    /**
     *    Get the migration classes that have not yet run.
     *
     *    @param  array $available The available migrations returned by the migration locator
     *    @param  array $ran The list of already ran migrations returned by the migration repository
     *    @return array The list of pending migrations, ie the available migrations not ran yet
     */
    protected function pendingMigrations($available, $ran)
    {
        return collect($available)->reject(function ($migration) use ($ran) {
            return collect($ran)->contains($migration);
        })->values()->all();
    }

    /**
     * Run an array of migrations.
     *
     * @param  array  $migrations
     * @param  array  $options
     * @return void
     */
    /**
     *    Run an array of migrations.
     *
     *    @param  array $migrations An array of migrations classes names to be run (unsorted, unvalidated)
     *    @param  array $options The options for the current operation [step, pretend]
     *    @return void
     */
    public function runPending(array $migrations, array $options = [])
    {
        // First we will just make sure that there are any migrations to run. If there
        // aren't, we will just make a note of it to the developer so they're aware
        // that all of the migrations have been run against this database system.
        if (count($migrations) == 0) {
            $this->note('<info>Nothing to migrate.</info>');

            return;
        }

        // Next we need to validate that all pending migration dependencies are met or WILL BE MET
        // This operation is important as it's the one that place the outstanding migrations
        // in the correct order, making sure a migration script won't fail because the table
        // it depends on has not been created yet (for example). Any migration without a fulfilled
        // dependency will cause this script to throw an exception
        $orderedMigrations = $migrations; //!TODO Implement this !

        // Next, we will get the next batch number for the migrations so we can insert
        // correct batch number in the database migrations repository when we store
        // each migration's execution.
        $batch = $this->repository->getNextBatchNumber();

        // We extract a few of the options.
        $pretend = $options['pretend'] ?? false;
        $step = $options['step'] ?? false;

        // We now have an ordered array of migrations, we will spin through them and run the
        // migrations "up" so the changes are made to the databases. We'll then log
        // that the migration was run so we don't repeat it next time we execute.
        foreach ($orderedMigrations as $migrationClass) {
            $this->runUp($migrationClass, $batch, $pretend);

            if ($step) {
                $batch++;
            }
        }
    }

    /**
     *    Run "up" a migration class
     *
     *    @param  string $migrationClass The migration class name
     *    @param  int $batch The current bacth number
     *    @param  bool $pretend If this operation should be pretended / faked
     *    @return void
     */
    protected function runUp($migrationClass, $batch, $pretend)
    {
        // First we will resolve a "real" instance of the migration class from
        // the class name. Once we have the instances we can run the actual
        // command such as "up" or "down", or we can just simulate the action.
        $migration = $this->resolve($migrationClass);

        // Move into pretend mode if requested
        if ($pretend) {
            return $this->pretendToRun($migration, 'up');
        }

        $this->note("<comment>Migrating:</comment> {$migrationClass}");

        // Run the actuall migration
        $this->runMigration($migration, 'up');

        // Once we have run a migrations class, we will log that it was run in this
        // repository so that we don't try to run it next time we do a migration
        // in the application. A migration repository keeps the migrate order.
        $this->repository->log($migrationClass, $batch);

        $this->note("<info>Migrated:</info>  {$migrationClass}");
    }

    /**
     * Rollback the last migration operation.
     *
     * @param  array|string $paths
     * @param  array  $options
     * @return array
     */
    /**
     *    Rollback the last migration operation.
     *
     *    @param  array $options The options for the current operation [step, pretend]
     *    @return array The list of rolledback migration classes
     */
    public function rollback(array $options = [])
    {
        $this->notes = [];

        //!TODO :: Add the sprinkle option

        // We want to pull in the last batch of migrations that ran on the previous
        // migration operation. We'll then reverse those migrations and run each
        // of them "down" to reverse the last migration "operation" which ran.
        $migrations = $this->getMigrationsForRollback($options);

        if (count($migrations) === 0) {
            $this->note('<info>Nothing to rollback.</info>');

            return [];
        } else {
            return $this->rollbackMigrations($migrations, $options);
        }
    }

    /**
     * Get the migrations for a rollback operation.
     *
     * @param  array  $options The options for the current operation
     * @return array  An ordered array of migrations to rollback
     */
    protected function getMigrationsForRollback(array $options)
    {
        if (($steps = $options['step'] ?? 0) > 0) {
            return $this->repository->getMigrations($steps);
        } else {
            return $this->repository->getLast();
        }
    }

    /**
     *    Rollback the given migrations.
     *
     *    @param  array $migrations An array of migrations to rollback
     *    @param  array $options The options for the current operation
     *    @return array The list of rolledback migration classes
     */
    protected function rollbackMigrations(array $migrations, array $options)
    {
        $rolledBack = [];

        // Get the available migration classes in the filesystem
        $availableMigrations = collect($this->getAvailableMigrations());

        // Next we will run through all of the migrations and call the "down" method
        // which will reverse each migration in order. This getLast method on the
        // repository already returns these migration's classenames in reverse order.
        foreach ($migrations as $migration) {

            // We have to make sure the class exist first
            if (!$availableMigrations->contains($migration)) {

                //!TODO :: Should this throw an exception instead ?
                $this->note("<fg=red>Migration not found:</> {$migration}");
                continue;
            }

            //!TODO :: Add dependency check. Not required if we rollback any data, but might be if sprinkle option is used

            // Add the migration to the list of rolledback migration
            $rolledBack[] = $migration;

            // Extract some options
            $pretend = $options['pretend'] ?? false;

            // Run the migration down
            $this->runDown($migration, $pretend);
        }

        return $rolledBack;
    }

    /**
     *    Rolls all of the currently applied migrations back.
     *
     *    @param boolean $pretend Should this operation be pretended
     *    @return array An array of all the rolledback migration classes
     */
    public function reset($pretend = false)
    {
        $this->notes = [];

        //!TODO :: Add the sprinkle option

        // We get the list of all the migrations class available and reverse
        // said list so we can run them back in the correct order for resetting
        // this database. This will allow us to get the database back into its
        // "empty" state and ready to be migrated "up" again.
        //
        // !TODO :: Should compare to the install list to make sure no outstanding migration (ran, but with no migraiton class anymore) still exist in the db
        $migrations = array_reverse($this->repository->getRan());

        if (count($migrations) === 0) {
            $this->note('<info>Nothing to rollback.</info>');

            return [];
        } else {
            return $this->rollbackMigrations($migrations, compact('pretend'));
        }
    }

    /**
     *    Run "down" a migration instance.
     *
     *    @param  string $migrationClass The migration class name
     *    @param  bool $pretend Is the operation should be pretended
     *    @return void
     */
    protected function runDown($migrationClass, $pretend)
    {
        // We resolve an instance of the migration. Once we get an instance we can either run a
        // pretend execution of the migration or we can run the real migration.
        $instance = $this->resolve($migrationClass);

        $this->note("<comment>Rolling back:</comment> {$migrationClass}");

        if ($pretend) {
            return $this->pretendToRun($instance, 'down');
        }

        $this->runMigration($instance, 'down');

        // Once we have successfully run the migration "down" we will remove it from
        // the migration repository so it will be considered to have not been run
        // by the application then will be able to fire by any later operation.
        $this->repository->delete($migrationClass);

        $this->note("<info>Rolled back:</info>  {$migrationClass}");
    }

    /**
     *    Run a migration inside a transaction if the database supports it.
     *
     *    @param  object $migration The migration instance
     *    @param  string $method The method used [up, down]
     *    @return void
     */
    protected function runMigration($migration, $method)
    {
        /*$connection = $this->resolveConnection(
            $migration->getConnection()
        );*/
        //!TODO : See if this can be done.

        //$callback = function () use ($migration, $method) {
            if (method_exists($migration, $method)) {
                $migration->{$method}();
            }
        //};

        /*$this->getSchemaGrammar($connection)->supportsSchemaTransactions()
                    ? $connection->transaction($callback)
                    : $callback();*/
    }

    /**
     *    Pretend to run the migrations.
     *
     *    @param  object $migration The migration instance
     *    @param  string $method The method used [up, down]
     *    @return void
     */
    protected function pretendToRun($migration, $method)
    {
        foreach ($this->getQueries($migration, $method) as $query) {
            $name = get_class($migration);

            $this->note("<info>{$name}:</info> {$query['query']}");
        }
    }

    /**
     *    Get all of the queries that would be run for a migration.
     *
     *    @param  object $migration The migration instance
     *    @param  string $method The method used [up, down]
     *    @return array The queries executed by the processed schema
     */
    protected function getQueries($migration, $method)
    {
        // Now that we have the connections we can resolve it and pretend to run the
        // queries against the database returning the array of raw SQL statements
        // that would get fired against the database system for this migration.
        /*$db = $this->resolveConnection(
            $migration->getConnection()
        );*/
        //!TODO : Fix me
        $db = $this->schema->getConnection();

        return $db->pretend(function () use ($migration, $method) {
            if (method_exists($migration, $method)) {
                $migration->{$method}();
            }
        });
    }

    /**
     *    Resolve a migration instance from it's class name.
     *
     *    @param  string $migrationClass The class name
     *    @return object The migration class instance
     */
    public function resolve($migrationClass)
    {
        return new $migrationClass($this->schema);
    }

    /**
     *    Get all of the migration files in a given path.
     *
     *    @return array The list of migration classes found in the filesystem
     */
    public function getAvailableMigrations()
    {
        return $this->locator->getMigrations();
    }

    /**
     * Set the default connection name.
     *
     * @param  string  $name
     * @return void
     */
    public function setConnection($name)
    {
        if (! is_null($name)) {
            $this->resolver->setDefaultConnection($name);
        }

        $this->repository->setSource($name);

        $this->connection = $name;
    }

    /**
     * Resolve the database connection instance.
     *
     * @param  string  $connection
     * @return \Illuminate\Database\Connection
     */
    public function resolveConnection($connection)
    {
        return $this->resolver->connection($connection ?: $this->connection);
    }

    /**
     * Get the schema grammar out of a migration connection.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @return \Illuminate\Database\Schema\Grammars\Grammar
     */
    protected function getSchemaGrammar($connection)
    {
        if (is_null($grammar = $connection->getSchemaGrammar())) {
            $connection->useDefaultSchemaGrammar();

            $grammar = $connection->getSchemaGrammar();
        }

        return $grammar;
    }

    /**
     *    Get the migration repository instance.
     *
     *    @return \Illuminate\Database\Migrations\MigrationRepositoryInterface
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     *    Determine if the migration repository exists.
     *
     *    @return bool If the repository exist
     */
    public function repositoryExists()
    {
        return $this->repository->repositoryExists();
    }

    /**
     *    Get the file system instance.
     *
     *    @return \UserFrosting\Sprinkle\Core\Database\MigrationLocatorInterface
     */
    public function getLocator()
    {
        return $this->locator;
    }

    /**
     *    Raise a note event for the migrator.
     *
     *    @param  string  $message The message
     *    @return void
     */
    protected function note($message)
    {
        $this->notes[] = $message;
    }

    /**
     *    Get the notes for the last operation.
     *
     *    @return array An array of notes
     */
    public function getNotes()
    {
        return $this->notes;
    }
}
