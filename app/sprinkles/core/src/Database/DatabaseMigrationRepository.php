<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UserFrosting
 * @license   https://github.com/userfrosting/UserFrosting/blob/master/licenses/UserFrosting.md (MIT License)
 */
namespace UserFrosting\Sprinkle\Core\Database;

use Illuminate\Database\Schema\Builder;
use UserFrosting\Sprinkle\Core\Database\MigrationRepositoryInterface;

/**
 * MigrationRepository Class
 *
 * Repository used to store all migrations run against the database
 *
 * @author Louis Charette
 */
class DatabaseMigrationRepository implements MigrationRepositoryInterface
{
    /**
     * The schema builder instance
     *
     * @var \Illuminate\Database\Schema\Builder
     */
    protected $schema;

    /**
     * The name of the migration table.
     *
     * @var string
     */
    protected $table;

    /**
     * Create a new database migration repository instance.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @param  string  $table
     * @return void
     */
    public function __construct(Builder $schema, $table = "migrations")
    {
        $this->table = $table;
        $this->schema = $schema;
    }

    /**
     * Get the ran migrations.
     *
     * @return array An array of migration class names
     */
    public function getRan()
    {
        return $this->table()
                ->orderBy('batch', 'asc')
                ->orderBy('migration', 'asc')
                ->pluck('migration')->all();
    }

    /**
     * Get list of migrations.
     *
     * @param  int  $steps
     * @return array
     */
    public function getMigrations($steps)
    {
        $query = $this->table()->where('batch', '>=', '1');

        return $query->orderBy('migration', 'desc')->take($steps)->get()->all();
    }

    /**
     * Get the last migration batch.
     *
     * @return array
     */
    public function getLast()
    {
        $query = $this->table()->where('batch', $this->getLastBatchNumber());

        return $query->orderBy('migration', 'desc')->get()->all();
    }

    /**
     * Log that a migration was run.
     *
     * @param  string  $file
     * @param  int     $batch
     * @param  string  $sprinkle
     * @return void
     */
    public function log($file, $batch, $sprinkle = "")
    {
        $record = ['migration' => $file, 'batch' => $batch, 'sprinkle' => $sprinkle];

        $this->table()->insert($record);
    }

    /**
     * Remove a migration from the log.
     *
     * @param  string  $migration
     * @return void
     */
    public function delete($migration)
    {
        $this->table()->where('migration', $migration)->delete();
    }

    /**
     * Get the next migration batch number.
     *
     * @return int
     */
    public function getNextBatchNumber()
    {
        return $this->getLastBatchNumber() + 1;
    }

    /**
     * Get the last migration batch number.
     *
     * @return int
     */
    public function getLastBatchNumber()
    {
        return $this->table()->max('batch');
    }

    /**
     * Create the migration repository data store.
     *
     * @return void
     */
    public function createRepository()
    {
        $this->schema->create($this->table, function ($table) {
            // The migrations table is responsible for keeping track of which of the
            // migrations have actually run for the application. We'll create the
            // table to hold the migration file's path as well as the batch ID.
            $table->increments('id');
            $table->string('sprinkle');
            $table->string('migration');
            $table->integer('batch');
            $table->timestamps();
        });
    }

    /**
     * Determine if the migration repository exists.
     *
     * @return bool
     */
    public function repositoryExists()
    {
        return $this->schema->hasTable($this->table);
    }

    /**
     * Get a query builder for the migration table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function table()
    {
        return $this->getConnection()->table($this->table);
    }

    public function getSchemaBuilder()
    {
        return $this->schema;
    }

    /**
     * Resolve the database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    public function getConnection()
    {
        return $this->schema->getConnection();
    }
}
