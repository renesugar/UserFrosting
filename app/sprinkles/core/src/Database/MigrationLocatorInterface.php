<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UserFrosting
 * @license   https://github.com/userfrosting/UserFrosting/blob/master/licenses/UserFrosting.md (MIT License)
 */
namespace UserFrosting\Sprinkle\Core\Database;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use UserFrosting\System\Sprinkle\SprinkleManager;

/**
 * MigrationLocatorInterface
 *
 * All MigrationLocator handlers must implement this interface.
 *
 * @author Louis Charette
 */
interface MigrationLocatorInterface
{
    /**
     *    Class Constructor
     *
     *    @param  SprinkleManager $sprinkleManager The sprinkle manager services
     *    @param  Filesystem $files The filesystem instance
     */
    public function __construct(SprinkleManager $sprinkleManager, Filesystem $files);

    /**
     *    Returm a list of all available migration available for a specific sprinkle
     *
     *    @param  string $sprinkleName The sprinkle name
     *    @return array The list of available migration classes
     */
    public function getMigrationsForSprinkle($sprinkleName);

    /**
     *    Loop all the available sprinkles and return a list of their migrations
     *
     *    @return array A list of all the migration files found for every sprinkle
     */
    public function getMigrations();
}
