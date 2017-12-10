<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UserFrosting
 * @license   https://github.com/userfrosting/UserFrosting/blob/master/licenses/UserFrosting.md (MIT License)
 */
namespace UserFrosting\Tests\Unit;

use UserFrosting\Tests\TestCase;

/**
 * FactoriesTest class.
 * Tests the factories defined in this sprinkle are working
 *
 * @extends TestCase
 */
class FactoriesTest extends TestCase
{
    public function setUp()
    {
        // Boot parent TestCase, which will set up the database and connections for us.
        parent::setUp();

        // Use the test_integration for this test
        $this->ci->db->getDatabaseManager()->setDefaultConnection('test_integration');

        // Setup the db
        $this->ci->migrator->run();
    }

    function testUserFactory()
    {
        $fm = $this->ci->factory;

        $user = $fm->create('UserFrosting\Sprinkle\Account\Database\Models\User');
        $this->assertInstanceOf('UserFrosting\Sprinkle\Account\Database\Models\User', $user);
    }
}
