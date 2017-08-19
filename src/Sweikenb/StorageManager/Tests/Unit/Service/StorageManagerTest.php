<?php
/**
 * @copyright Copyright (c) 2017 Simon Schröer <https://schroeer.me>
 *
 * @see LICENSE
 */

use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Sweikenb\StorageManager\Api\StorageAdapterInterface;
use Sweikenb\StorageManager\Service\StorageManager;

/**
 * @covers \Sweikenb\StorageManager\Service\StorageManager
 * @covers \Sweikenb\StorageManager\Abstracts\AbstractStorageManager
 */
class StorageManagerTest extends TestCase
{
    /**
     * @var StorageAdapterInterface|Mock
     */
    private $adapterMock;

    /**
     * @var StorageManager
     */
    private $manager;

    public function setUp()
    {
        $this->adapterMock = $this->getMockBuilder(StorageAdapterInterface::class)
            ->getMock();

        $this->manager = new StorageManager($this->adapterMock);
    }

    public function testConstructor()
    {
        $this->assertAttributeSame($this->adapterMock, 'storageAdapter', $this->manager);
    }

    public function testGet()
    {
        $key = 'testkey';
        $value = ['test', 'value'];

        $this->adapterMock
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo($key))
            ->willReturn($value);

        $this->assertEquals($value, $this->manager->get($key));
    }

    public function dataProviderForStatus()
    {
        return [[true], [false]];
    }

    /**
     * @dataProvider dataProviderForStatus
     *
     * @param bool $status
     */
    public function testSet(bool $status)
    {
        $key = 'testkey';
        $value = ['test', 'value'];

        $this->adapterMock
            ->expects($this->once())
            ->method('set')
            ->with($this->equalTo($key), $this->equalTo($value))
            ->willReturn($status);

        $this->assertSame($status, $this->manager->set($key, $value));
    }

    /**
     * @dataProvider dataProviderForStatus
     *
     * @param bool $status
     */
    public function testDelete(bool $status)
    {
        $keys = ['testkey1', 'testkey2'];

        $this->adapterMock
            ->expects($this->once())
            ->method('delete')
            ->with($this->equalTo($keys))
            ->willReturn($status);

        $this->assertSame($status, $this->manager->delete($keys));
    }

    public function dataProviderForGetList()
    {
        return [
            [100, null, null],
            [100, 1, null],
            [100, 1, 2],
        ];
    }

    /**
     * @dataProvider dataProviderForGetList
     *
     * @param int $limit
     * @param int|null $offset
     * @param int|null $orderig
     */
    public function testGetList(int $limit, int $offset = null, int $orderig = null)
    {
        $result = [['item1'], ['item2']];

        $this->adapterMock
            ->expects($this->once())
            ->method('getList')
            ->with($this->equalTo($limit), $this->equalTo($offset), $this->equalTo($orderig))
            ->willReturn($result);

        $this->assertEquals($result, $this->manager->getList($limit, $offset, $orderig));
    }

    public function dataProviderForGetAll()
    {
        return [[null], [1]];
    }

    /**
     * @dataProvider dataProviderForGetAll
     *
     * @param int|null $orderig
     */
    public function testGetAll(int $orderig = null)
    {
        $result = [['item1'], ['item2']];

        $this->adapterMock
            ->expects($this->once())
            ->method('getAll')
            ->with($this->equalTo($orderig))
            ->willReturn($result);

        $this->assertEquals($result, $this->manager->getAll($orderig));
    }

    public function testGetOldestItem()
    {
        $result = ['item1'];

        $this->adapterMock
            ->expects($this->once())
            ->method('getList')
            ->with(
                $this->equalTo(1),
                $this->equalTo(0),
                $this->equalTo(StorageAdapterInterface::ORDER_ASC)
            )
            ->willReturn($result);

        $this->assertEquals($result, $this->manager->getOldestItem());
    }

    public function testNewestItem()
    {
        $result = ['item1'];

        $this->adapterMock
            ->expects($this->once())
            ->method('getList')
            ->with(
                $this->equalTo(1),
                $this->equalTo(0),
                $this->equalTo(StorageAdapterInterface::ORDER_DESC)
            )
            ->willReturn($result);

        $this->assertEquals($result, $this->manager->getNewestItem());
    }
}
