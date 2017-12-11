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
use UserFrosting\Sprinkle\Core\Database\Migrator\MigrationLocator;

class MigrationLocatorTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testGetMigrations()
    {
        // Simulate the SprinkleManager
        $sprinkleManager = m::mock('UserFrosting\System\Sprinkle\SprinkleManager');

        // The locator will ask sprinkleManager for `getSprinkleNames`. When it does, we'll return this fake data
        $sprinkleManager->shouldReceive('getSprinkleNames')->once()->andReturn([
            'core',
            'Account'
        ]);

        // Simulate the Filesystem.
        $filesystem = m::mock('Illuminate\Filesystem\Filesystem');

        // When `MigrationLocator` will ask the filesystem for `glob` and `core` sprinkle,
        // filesystem will return fake test path.
        $filesystem->shouldReceive('glob')->with(\UserFrosting\ROOT_DIR . "/app/sprinkles/core/src/Database/Migrations/*/*.php")->once()->andReturn([
            \UserFrosting\ROOT_DIR . '/app/sprinkles/core/src/Database/Migrations/one/CreateUsersTable.php',
            \UserFrosting\ROOT_DIR . '/app/sprinkles/core/src/Database/Migrations/one/CreatePasswordResetsTable.php',
            \UserFrosting\ROOT_DIR . '/app/sprinkles/core/src/Database/Migrations/two/CreateFlightsTable.php',
            \UserFrosting\ROOT_DIR . '/app/sprinkles/core/src/Database/Migrations/CreateMainTable.php'
        ]);

        // When `MigrationLocator` will also ask the filesystem the same thing, but for the `account` sprinkle
        $filesystem->shouldReceive('glob')->with(\UserFrosting\ROOT_DIR . "/app/sprinkles/Account/src/Database/Migrations/*/*.php")->once()->andReturn([
            \UserFrosting\ROOT_DIR . '/app/sprinkles/Account/src/Database/Migrations/one/CreateUsersTable.php',
            \UserFrosting\ROOT_DIR . '/app/sprinkles/Account/src/Database/Migrations/one/CreatePasswordResetsTable.php',
            \UserFrosting\ROOT_DIR . '/app/sprinkles/Account/src/Database/Migrations/two/CreateFlightsTable.php',
            \UserFrosting\ROOT_DIR . '/app/sprinkles/Account/src/Database/Migrations/CreateMainTable.php'
        ]);

        // Create a new MigrationLocator instance with our simulated SprinkleManager and filesystem
        // and ask to find core sprinkle migration files
        $locator = new MigrationLocator($sprinkleManager, $filesystem);
        $results = $locator->getMigrations();

        // The `getMigrationForSprinkle` method should return this
        $expected = [
            "\\UserFrosting\\Sprinkle\\Account\\Database\\Migrations\\one\\CreateUsersTable",
            "\\UserFrosting\\Sprinkle\\Account\\Database\\Migrations\\one\\CreatePasswordResetsTable",
            "\\UserFrosting\\Sprinkle\\Account\\Database\\Migrations\\two\\CreateFlightsTable",
            "\\UserFrosting\\Sprinkle\\Account\\Database\\Migrations\\CreateMainTable",
            "\\UserFrosting\\Sprinkle\\Core\\Database\\Migrations\\one\\CreateUsersTable",
            "\\UserFrosting\\Sprinkle\\Core\\Database\\Migrations\\one\\CreatePasswordResetsTable",
            "\\UserFrosting\\Sprinkle\\Core\\Database\\Migrations\\two\\CreateFlightsTable",
            "\\UserFrosting\\Sprinkle\\Core\\Database\\Migrations\\CreateMainTable"
        ];

        // Test results match expectations
        $this->assertEquals($expected, $results);
    }

    public function testGetMigrationsForSprinkle()
    {
        // Simulate the SprinkleManager
        $sprinkleManager = m::mock('UserFrosting\System\Sprinkle\SprinkleManager');

        // Simulate the Filesystem.
        $filesystem = m::mock('Illuminate\Filesystem\Filesystem');

        // When `MigrationLocator` will ask the filesystem for `glob`, which it should do only once,
        // filesystem will return fake test path.
        $filesystem->shouldReceive('glob')->with(\UserFrosting\ROOT_DIR . "/app/sprinkles/core/src/Database/Migrations/*/*.php")->once()->andReturn([
            \UserFrosting\ROOT_DIR . '/app/sprinkles/core/src/Database/Migrations/one/CreateUsersTable.php',
            \UserFrosting\ROOT_DIR . '/app/sprinkles/core/src/Database/Migrations/one/CreatePasswordResetsTable.php',
            \UserFrosting\ROOT_DIR . '/app/sprinkles/core/src/Database/Migrations/two/CreateFlightsTable.php',
            \UserFrosting\ROOT_DIR . '/app/sprinkles/core/src/Database/Migrations/CreateMainTable.php'
        ]);

        // Create a new MigrationLocator instance with our simulated SprinkleManager and filesystem
        // and ask to find core sprinkle migration files
        $locator = new MigrationLocator($sprinkleManager, $filesystem);
        $results = $locator->getMigrationsForSprinkle('core');

        // The `getMigrationForSprinkle` method should return this
        $expected = [
            "\\UserFrosting\\Sprinkle\\Core\\Database\\Migrations\\one\\CreateUsersTable",
            "\\UserFrosting\\Sprinkle\\Core\\Database\\Migrations\\one\\CreatePasswordResetsTable",
            "\\UserFrosting\\Sprinkle\\Core\\Database\\Migrations\\two\\CreateFlightsTable",
            "\\UserFrosting\\Sprinkle\\Core\\Database\\Migrations\\CreateMainTable"
        ];

        // Test results match expectations
        $this->assertEquals($expected, $results);
    }
}
