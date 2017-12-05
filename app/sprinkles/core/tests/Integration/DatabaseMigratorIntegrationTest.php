<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UserFrosting
 * @license   https://github.com/userfrosting/UserFrosting/blob/master/licenses/UserFrosting.md (MIT License)
 */
namespace UserFrosting\Tests\Integration;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use UserFrosting\Sprinkle\Core\Database\DatabaseMigrationRepository;
use UserFrosting\Sprinkle\Core\Database\MigrationLocator;
use UserFrosting\Sprinkle\Core\Database\Migrator;
use UserFrosting\Tests\TestCase;

class DatabaseMigratorIntegrationTest extends TestCase
{
    protected $schemaName = 'test_integration';

    protected $migrationTable = 'migrations';

    protected $schema;

    protected $migrator;

    protected $locator;

    /**
     * Bootstrap Eloquent.
     *
     * @return void
     */
    public function setUp()
    {
        // Boot parent TestCase, which will set up the database and connections for us.
        parent::setUp();

        // Boot database
        $db = $this->ci->db;

        // Get the schema instance
        $this->schema = $db->connection($this->schemaName)->getSchemaBuilder();

        // Get the repository instance. Set the correct database
        $repository = new DatabaseMigrationRepository($this->schema, $this->migrationTable);

        // Get the Locator
        $this->locator = new MigrationLocatorStub($this->ci->sprinkleManager, new Filesystem);

        // Get the migrator instance
        $this->migrator = new Migrator($repository, $this->schema, $this->locator);

        if (!$repository->repositoryExists()) {
            $repository->createRepository();
        }
    }

    /*public function tearDown()
    {
        m::close();
    }*/

    public function testMigrationRepositoryCreated()
    {
        $this->assertTrue($this->schema->hasTable($this->migrationTable));
    }

    public function testBasicMigration()
    {
        $ran = $this->migrator->run();

        $this->assertTrue($this->schema->hasTable('users'));
        $this->assertTrue($this->schema->hasTable('password_resets'));

        $this->assertEquals($this->locator->getMigrations(), $ran);
    }

    /*public function testMigrationsCanBeRolledBack()
    {
        // Run up
        $this->migrator->run();
        $this->assertTrue($this->schema->hasTable('users'));
        $this->assertTrue($this->schema->hasTable('password_resets'));

        $rolledBack = $this->migrator->rollback(); <-- Doesn't work so far !
        $this->assertFalse($this->schema->hasTable('users'));
        $this->assertFalse($this->schema->hasTable('password_resets'));

        $this->assertEquals($this->locator->getMigrations(), $rolledBack);
    }*/

    /*public function testMigrationsCanBeReset()
    {
        $this->migrator->run([__DIR__.'/migrations/one']);
        $this->assertTrue($this->schema->hasTable('users'));
        $this->assertTrue($this->schema->hasTable('password_resets'));
        $rolledBack = $this->migrator->reset([__DIR__.'/migrations/one']);
        $this->assertFalse($this->schema->hasTable('users'));
        $this->assertFalse($this->schema->hasTable('password_resets'));

        $this->assertTrue(Str::contains($rolledBack[0], 'password_resets'));
        $this->assertTrue(Str::contains($rolledBack[1], 'users'));
    }

    public function testNoErrorIsThrownWhenNoOutstandingMigrationsExist()
    {
        $this->migrator->run([__DIR__.'/migrations/one']);
        $this->assertTrue($this->schema->hasTable('users'));
        $this->assertTrue($this->schema->hasTable('password_resets'));
        $this->migrator->run([__DIR__.'/migrations/one']);
    }

    public function testNoErrorIsThrownWhenNothingToRollback()
    {
        $this->migrator->run([__DIR__.'/migrations/one']);
        $this->assertTrue($this->schema->hasTable('users'));
        $this->assertTrue($this->schema->hasTable('password_resets'));
        $this->migrator->rollback([__DIR__.'/migrations/one']);
        $this->assertFalse($this->schema->hasTable('users'));
        $this->assertFalse($this->schema->hasTable('password_resets'));
        $this->migrator->rollback([__DIR__.'/migrations/one']);
    }*/

    /**
     *    !TODO :
     *    - Test pretend on up, rollback, reset
     *    - Test unfulfillable migrations
     */
}

class MigrationLocatorStub extends MigrationLocator {
    public function getMigrations()
    {
        return [
            '\\UserFrosting\\Tests\\Integration\\Migrations\\one\\CreateUsersTable',
            '\\UserFrosting\\Tests\\Integration\\Migrations\\one\\CreatePasswordResetsTable'
        ];
    }
}
