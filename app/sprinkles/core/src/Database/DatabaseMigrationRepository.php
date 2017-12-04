<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UserFrosting
 * @license   https://github.com/userfrosting/UserFrosting/blob/master/licenses/UserFrosting.md (MIT License)
 */
namespace UserFrosting\Sprinkle\Core\Database;

use Illuminate\Database\Migrations\DatabaseMigrationRepository as Repository;

/**
 * MigrationRepository Class
 *
 * Repository used to store all migrations run against the database
 *
 * @author Louis Charette
 */
class DatabaseMigrationRepository extends Repository
{
    /**
     * Log that a migration was run.
     *
     * @param  string  $file
     * @param  int  $batch
     * @return void
     */
    public function log($file, $batch, $sprinkle = "")
    {
        $record = ['migration' => $file, 'batch' => $batch, 'sprinkle' => $sprinkle];

        $this->table()->insert($record);
    }

    /**
     * Create the migration repository data store.
     *
     * @return void
     */
    public function createRepository()
    {
        $schema = $this->getConnection()->getSchemaBuilder();

        $schema->create($this->table, function ($table) {
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
}
