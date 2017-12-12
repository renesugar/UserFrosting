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

class WithTestDatabaseTraitTest extends TestCase
{
    use WithTestDatabase;

    /**
     *    Test the WithTestDatabase traits works
     */
    public function testTrait()
    {
        // Use the test_integration for this test
        $connection = $this->ci->db->getConnection();
        $this->assertEquals('test_integration', $connection->getName());
    }
}
