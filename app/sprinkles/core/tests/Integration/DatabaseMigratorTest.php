<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UserFrosting
 * @license   https://github.com/userfrosting/UserFrosting/blob/master/licenses/UserFrosting.md (MIT License)
 */
namespace UserFrosting\Tests\Integration;

use Mockery as m;
use UserFrosting\Tests\TestCase;
use UserFrosting\Sprinkle\Core\Database\Migrator;

/**
 *    Tests for the Migrator Class
 *    
 *    Theses tests make sure the Migrator works correctly, without validating
 *    agaist a simulated database. Those tests are performed by `DatabaseMigratorIntegrationTest`
 *
 *    @author Louis Charette
 */
class DatabaseMigratorTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    /**
     *    Basic test to make sure the base method syntaxt is ok
     */
    public function testMigratorUpWithNoMigrations()
    {
        // Mock the migrator dependencies
        $repository = m::mock('UserFrosting\Sprinkle\Core\Database\DatabaseMigrationRepository');
        $schemaBuilder = m::mock('Illuminate\Database\Schema\Builder');
        $locator = m::mock('UserFrosting\Sprinkle\Core\Database\MigrationLocator');

        // Build migrator
        $migrator = new Migrator($repository, $schemaBuilder, $locator);

        // Locator will be asked to return the avaialble migrations
        $locator->shouldReceive('getMigrations')->once()->andReturn([]);

        // Repository will be asked to return the ran migrations
        $repository->shouldReceive('getRan')->once()->andReturn([]);

        $migrations = $migrator->run();
        $this->assertEmpty($migrations);
    }

    /**
     *    Basic test where all avaialble migrations are pending and fulfillable
     */
    public function testMigratorUpWithOnlyPendingMigrations()
    {
        // Mock the migrator dependencies
        $repository = m::mock('UserFrosting\Sprinkle\Core\Database\DatabaseMigrationRepository');
        $schemaBuilder = m::mock('Illuminate\Database\Schema\Builder');
        $locator = m::mock('UserFrosting\Sprinkle\Core\Database\MigrationLocator');

        // Build migrator
        $migrator = new Migrator($repository, $schemaBuilder, $locator);

        // The migrations set
        $testMigrations = [
            '\\UserFrosting\\Tests\\Integration\\Migrations\\one\\CreateUsersTable',
            '\\UserFrosting\\Tests\\Integration\\Migrations\\one\\CreatePasswordResetsTable',
            '\\UserFrosting\\Tests\\Integration\\Migrations\\two\\CreateFlightsTable'
        ];

        // When running up, Locator will return all 3 migration classes
        $locator->shouldReceive('getMigrations')->andReturn($testMigrations);

        // Repository will be asked to return the ran migrations, the next batch number and will log 3 new migrations
        $repository->shouldReceive('getRan')->andReturn([]);
        $repository->shouldReceive('getNextBatchNumber')->andReturn(1);
        $repository->shouldReceive('log')->times(3)->andReturn(null);

        // SchemaBuilder will create all 3 tables
        $schemaBuilder->shouldReceive('create')->times(3)->andReturn(null);

        // Run migrations up
        $migrations = $migrator->run();

        // All classes should have been migrated
        $this->assertEquals($testMigrations, $migrations);
    }

    /**
     *    Test where one of the avaialble migrations is already installed
     */
    public function testMigratorUpWithOneInstalledMigrations()
    {
        // Mock the migrator dependencies
        $repository = m::mock('UserFrosting\Sprinkle\Core\Database\DatabaseMigrationRepository');
        $schemaBuilder = m::mock('Illuminate\Database\Schema\Builder');
        $locator = m::mock('UserFrosting\Sprinkle\Core\Database\MigrationLocator');

        // Build migrator
        $migrator = new Migrator($repository, $schemaBuilder, $locator);

        // When running up, Locator will return all 3 migration classes
        $locator->shouldReceive('getMigrations')->andReturn([
            '\\UserFrosting\\Tests\\Integration\\Migrations\\one\\CreateUsersTable',
            '\\UserFrosting\\Tests\\Integration\\Migrations\\one\\CreatePasswordResetsTable',
            '\\UserFrosting\\Tests\\Integration\\Migrations\\two\\CreateFlightsTable'
        ]);

        // Repository will be asked to return the ran migrations (one), the next batch number and will log 2 new migrations
        $repository->shouldReceive('getRan')->andReturn([
            '\\UserFrosting\\Tests\\Integration\\Migrations\\one\\CreateUsersTable'
        ]);
        $repository->shouldReceive('getNextBatchNumber')->andReturn(2);
        $repository->shouldReceive('log')->times(2)->andReturn(null);

        // SchemaBuilder will only create 2 tables
        $schemaBuilder->shouldReceive('create')->times(2)->andReturn(null);

        // Run migrations up
        $migrations = $migrator->run();

        // The migration already ran shoudn't be in the pending ones
        $this->assertEquals([
            '\\UserFrosting\\Tests\\Integration\\Migrations\\one\\CreatePasswordResetsTable',
            '\\UserFrosting\\Tests\\Integration\\Migrations\\two\\CreateFlightsTable'
        ], $migrations);
    }

    /**
     *    Test where all avaialble migrations have been ran
     */
    public function testMigratorUpWithNoPendingMigrations()
    {
        // Mock the migrator dependencies
        $repository = m::mock('UserFrosting\Sprinkle\Core\Database\DatabaseMigrationRepository');
        $schemaBuilder = m::mock('Illuminate\Database\Schema\Builder');
        $locator = m::mock('UserFrosting\Sprinkle\Core\Database\MigrationLocator');

        // Build migrator
        $migrator = new Migrator($repository, $schemaBuilder, $locator);

        // The migrations set
        $testMigrations = [
            '\\UserFrosting\\Tests\\Integration\\Migrations\\one\\CreateUsersTable',
            '\\UserFrosting\\Tests\\Integration\\Migrations\\one\\CreatePasswordResetsTable',
            '\\UserFrosting\\Tests\\Integration\\Migrations\\two\\CreateFlightsTable'
        ];

        // When running up, Locator will return all 3 migration classes
        $locator->shouldReceive('getMigrations')->andReturn($testMigrations);

        // Repository will be asked to return the ran migrations (one), the next batch number and will log 2 new migrations
        $repository->shouldReceive('getRan')->andReturn($testMigrations);
        $repository->shouldNotReceive('getNextBatchNumber');
        $repository->shouldNotReceive('log');

        // SchemaBuilder will only create 2 tables
        $schemaBuilder->shouldNotReceive('create');

        // Run migrations up
        $migrations = $migrator->run();

        // The migration already ran shoudn't be in the pending ones
        $this->assertEquals([], $migrations);
    }

    /**
     *    Test where one of the available migrations is missing a dependency
     */
    //!TODO

    /**
     *    Test rolling back where no migrations have been ran
     */
    public function testMigratorRollbackWithNoInstalledMigrations()
    {
        // Mock the migrator dependencies
        $repository = m::mock('UserFrosting\Sprinkle\Core\Database\DatabaseMigrationRepository');
        $schemaBuilder = m::mock('Illuminate\Database\Schema\Builder');
        $locator = m::mock('UserFrosting\Sprinkle\Core\Database\MigrationLocator');

        // Build migrator
        $migrator = new Migrator($repository, $schemaBuilder, $locator);

        // Repository will be asked to return the last batch of ran migrations
        $repository->shouldReceive('getLast')->andReturn([]);

        // Run migrations up
        $migrations = $migrator->rollback();

        // The migration already ran shoudn't be in the pending ones
        $this->assertEquals([], $migrations);
    }

    /**
     *    Test rolling back all installed migrations
     */
    public function testMigratorRollbackAllInstalledMigrations()
    {
        // Mock the migrator dependencies
        $repository = m::mock('UserFrosting\Sprinkle\Core\Database\DatabaseMigrationRepository');
        $schemaBuilder = m::mock('Illuminate\Database\Schema\Builder');
        $locator = m::mock('UserFrosting\Sprinkle\Core\Database\MigrationLocator');

        // Build migrator
        $migrator = new Migrator($repository, $schemaBuilder, $locator);

        // The migrations set
        $testMigrations = [
            '\\UserFrosting\\Tests\\Integration\\Migrations\\one\\CreateUsersTable',
            '\\UserFrosting\\Tests\\Integration\\Migrations\\one\\CreatePasswordResetsTable',
            '\\UserFrosting\\Tests\\Integration\\Migrations\\two\\CreateFlightsTable'
        ];

        // When running up, Locator will return all 3 migration classes
        $locator->shouldReceive('getMigrations')->once()->andReturn($testMigrations);

        // Repository will be asked to return the ran migrations (one), the next batch number and will log 2 new migrations
        $repository->shouldReceive('getLast')->once()->andReturn($testMigrations);
        $repository->shouldReceive('delete')->times(3)->andReturn([]);

        // SchemaBuilder will only create 2 tables
        $schemaBuilder->shouldReceive('dropIfExists')->times(3)->andReturn([]);

        // Run migrations up
        $migrations = $migrator->rollback();

        // The migration already ran shoudn't be in the pending ones
        $this->assertEquals($testMigrations, $migrations);
    }

    /**
     *    Test where one of the installed migration is not in the available migration classes
     */
    public function testMigratorRollbackAllInstalledMigrationsWithOneMissing()
    {
        // Mock the migrator dependencies
        $repository = m::mock('UserFrosting\Sprinkle\Core\Database\DatabaseMigrationRepository');
        $schemaBuilder = m::mock('Illuminate\Database\Schema\Builder');
        $locator = m::mock('UserFrosting\Sprinkle\Core\Database\MigrationLocator');

        // Build migrator
        $migrator = new Migrator($repository, $schemaBuilder, $locator);

        // When running up, Locator will return all 3 migration classes
        $locator->shouldReceive('getMigrations')->once()->andReturn([
            '\\UserFrosting\\Tests\\Integration\\Migrations\\one\\CreatePasswordResetsTable'
        ]);

        // Repository will be asked to return the ran migrations (one), the next batch number and will log 2 new migrations
        $repository->shouldReceive('getLast')->once()->andReturn([
            '\\UserFrosting\\Tests\\Integration\\Migrations\\one\\CreateUsersTable',
            '\\UserFrosting\\Tests\\Integration\\Migrations\\one\\CreatePasswordResetsTable'
        ]);
        $repository->shouldReceive('delete')->times(1)->andReturn([]);

        // SchemaBuilder will only create 2 tables
        $schemaBuilder->shouldReceive('dropIfExists')->times(1)->andReturn([]);

        // Run migrations up
        $migrations = $migrator->rollback();

        // The migration already ran shoudn't be in the pending ones
        $this->assertEquals([
            '\\UserFrosting\\Tests\\Integration\\Migrations\\one\\CreatePasswordResetsTable'
        ], $migrations);
    }

    /**
     *    Test where one of the installed migration is not in the available migration classes
     */
    public function testMigratorResetAllInstalledMigrations()
    {
        // Mock the migrator dependencies
        $repository = m::mock('UserFrosting\Sprinkle\Core\Database\DatabaseMigrationRepository');
        $schemaBuilder = m::mock('Illuminate\Database\Schema\Builder');
        $locator = m::mock('UserFrosting\Sprinkle\Core\Database\MigrationLocator');

        // Build migrator
        $migrator = new Migrator($repository, $schemaBuilder, $locator);

        // The migrations set
        $testMigrations = [
            '\\UserFrosting\\Tests\\Integration\\Migrations\\one\\CreateUsersTable',
            '\\UserFrosting\\Tests\\Integration\\Migrations\\one\\CreatePasswordResetsTable',
            '\\UserFrosting\\Tests\\Integration\\Migrations\\two\\CreateFlightsTable'
        ];

        // When running up, Locator will return all 3 migration classes
        $locator->shouldReceive('getMigrations')->once()->andReturn($testMigrations);

        // Repository will be asked to return the ran migrations (one), the next batch number and will log 2 new migrations
        $repository->shouldReceive('getRan')->once()->andReturn($testMigrations);
        $repository->shouldReceive('delete')->times(3)->andReturn([]);

        // SchemaBuilder will only create 2 tables
        $schemaBuilder->shouldReceive('dropIfExists')->times(3)->andReturn([]);

        // Run migrations up
        $migrations = $migrator->reset();

        // The migration already ran shoudn't be in the pending ones
        $this->assertEquals(array_reverse($testMigrations), $migrations);
    }
}
