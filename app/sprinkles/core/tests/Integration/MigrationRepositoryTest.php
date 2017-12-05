<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UserFrosting
 * @license   https://github.com/userfrosting/UserFrosting/blob/master/licenses/UserFrosting.md (MIT License)
 */
namespace UserFrosting\Tests\Integration;

use Mockery as m;
use Illuminate\Support\Collection;
use UserFrosting\Sprinkle\Core\Database\DatabaseMigrationRepository;
use UserFrosting\Tests\TestCase;

class MigrationRepositoryTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testGetRanMigrationsListMigrationsByPackage()
    {
        $repo = $this->getRepository();
        $query = m::mock('stdClass');
        $connectionMock = m::mock('Illuminate\Database\Connection');

        // Setup expectations for SchemaBuilder and Connection
        $repo->getSchemaBuilder()->shouldReceive('getConnection')->andReturn($connectionMock);
        $repo->getConnection()->shouldReceive('table')->once()->with('migrations')->andReturn($query);

        // When getRan is called, the $connectionMock should be orderedBy batch, then migration and pluck.
        $query->shouldReceive('orderBy')->once()->with('id', 'asc')->andReturn(new Collection([['migration' => 'bar']]));

        $this->assertEquals(['bar'], $repo->getRan());
    }

    public function testGetLastMigrationsGetsAllMigrationsWithTheLatestBatchNumber()
    {
        $repo = $this->getMockBuilder('UserFrosting\Sprinkle\Core\Database\DatabaseMigrationRepository')->setMethods(['getLastBatchNumber'])->setConstructorArgs([
            m::mock('Illuminate\Database\Schema\Builder'), 'migrations',
        ])->getMock();
        $repo->expects($this->once())->method('getLastBatchNumber')->will($this->returnValue(1));
        $query = m::mock('stdClass');
        $connectionMock = m::mock('Illuminate\Database\Connection');

        // Setup expectations for SchemaBuilder and Connection
        $repo->getSchemaBuilder()->shouldReceive('getConnection')->andReturn($connectionMock);
        $repo->getConnection()->shouldReceive('table')->once()->with('migrations')->andReturn($query);

        // Table should be asked to search for batch 1, orderBy migration and return the foo migration
        $query->shouldReceive('where')->once()->with('batch', 1)->andReturn($query);
        $query->shouldReceive('orderBy')->once()->with('id', 'desc')->andReturn($query);
        $query->shouldReceive('get')->once()->andReturn(new Collection([['migration' => 'foo']]));

        $this->assertEquals(['foo'], $repo->getLast());
    }

    public function testLogMethodInsertsRecordIntoMigrationTable()
    {
        $repo = $this->getRepository();
        $query = m::mock('stdClass');
        $connectionMock = m::mock('Illuminate\Database\Connection');

        // Setup expectations for SchemaBuilder and Connection
        $repo->getSchemaBuilder()->shouldReceive('getConnection')->andReturn($connectionMock);
        $repo->getConnection()->shouldReceive('table')->once()->with('migrations')->andReturn($query);

        // When log is called, the table query should receive the insert statement
        $query->shouldReceive('insert')->once()->with(['migration' => 'bar', 'batch' => 1, 'sprinkle' => '']);

        $repo->log('bar', 1);
    }

    public function testDeleteMethodRemovesAMigrationFromTheTable()
    {
        $repo = $this->getRepository();
        $query = m::mock('stdClass');
        $connectionMock = m::mock('Illuminate\Database\Connection');

        // Setup expectations for SchemaBuilder and Connection
        $repo->getSchemaBuilder()->shouldReceive('getConnection')->andReturn($connectionMock);
        $repo->getConnection()->shouldReceive('table')->once()->with('migrations')->andReturn($query);

        // When delete is called, a where and delete operation should be performed on the table
        $query->shouldReceive('where')->once()->with('migration', 'foo')->andReturn($query);
        $query->shouldReceive('delete')->once();

        $repo->delete('foo');
    }

    public function testGetNextBatchNumberReturnsLastBatchNumberPlusOne()
    {
        $repo = $this->getMockBuilder('UserFrosting\Sprinkle\Core\Database\DatabaseMigrationRepository')->setMethods(['getLastBatchNumber'])->setConstructorArgs([
            m::mock('Illuminate\Database\Schema\Builder'), 'migrations',
        ])->getMock();
        $repo->expects($this->once())->method('getLastBatchNumber')->will($this->returnValue(1));

        $this->assertEquals(2, $repo->getNextBatchNumber());
    }

    public function testGetLastBatchNumberReturnsMaxBatch()
    {
        $repo = $this->getRepository();
        $query = m::mock('stdClass');
        $connectionMock = m::mock('Illuminate\Database\Connection');

        // Setup expectations for SchemaBuilder and Connection
        $repo->getSchemaBuilder()->shouldReceive('getConnection')->andReturn($connectionMock);
        $repo->getConnection()->shouldReceive('table')->once()->with('migrations')->andReturn($query);

        // When asked about the last batch number, table should be asked for the max value
        $query->shouldReceive('max')->once()->andReturn(1);

        $this->assertEquals(1, $repo->getLastBatchNumber());
    }

    public function testCreateRepositoryCreatesProperDatabaseTable()
    {
        $repo = $this->getRepository();
        $connectionMock = m::mock('Illuminate\Database\Connection');

        // Setup expectations for SchemaBuilder. When asked to create the repository, the schema should reeceive the create command
        $repo->getSchemaBuilder()->shouldReceive('getConnection')->andReturn($connectionMock);
        $repo->getSchemaBuilder()->shouldReceive('create')->once()->with('migrations', m::type('Closure'));

        $repo->createRepository();
    }

    protected function getRepository()
    {
        return new DatabaseMigrationRepository(m::mock('Illuminate\Database\Schema\Builder'), 'migrations');
    }
}
