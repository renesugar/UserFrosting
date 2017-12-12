<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UserFrosting
 * @license   https://github.com/userfrosting/UserFrosting/blob/master/licenses/UserFrosting.md (MIT License)
 */
namespace UserFrosting\Tests\Unit;

use UserFrosting\Tests\TestCase;
use UserFrosting\Tests\WithTestDatabase;
use UserFrosting\Tests\RefreshDatabase;

/**
 * FactoriesTest class.
 * Tests the factories defined in this sprinkle are working
 */
class FactoriesTest extends TestCase
{
    use WithTestDatabase;
    use RefreshDatabase;

    function testUserFactory()
    {
        $fm = $this->ci->factory;

        $user = $fm->create('UserFrosting\Sprinkle\Account\Database\Models\User');
        $this->assertInstanceOf('UserFrosting\Sprinkle\Account\Database\Models\User', $user);
    }
}
