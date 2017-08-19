<?php
/**
 * @copyright Copyright (c) 2017 Simon Schröer <https://schroeer.me>
 *
 * @see LICENSE
 */

use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Sweikenb\StorageManager\Adapter\SqlLiteStorageAdapter;

/**
 * @covers \Sweikenb\StorageManager\Adapter\SqlLiteStorageAdapter
 */
class SqlLiteStorageAdapterTest extends TestCase
{
    /**
     * @var string
     */
    private $dbName;

    /**
     * @var string
     */
    private $dbPrefix;

    /**
     * @var Mock
     */
    private $dbMock;

    /**
     * @var SqlLiteStorageAdapter|Mock
     */
    private $adapter;

    /**
     * @var int
     */
    private $adapterTime;

    public function setUp()
    {
        $this->dbName = 'dbname';
        $this->dbPrefix = 'dbpref';

        $this->adapter = $this->getMockBuilder(SqlLiteStorageAdapter::class)
            ->setConstructorArgs([$this->dbName, $this->dbPrefix])
            ->setMethods(['getFreshSqliteInstance'])
            ->getMock();

        $this->dbMock = $this->getMockBuilder(SQLite3::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->adapter
            ->expects($this->any())
            ->method('getFreshSqliteInstance')
            ->willReturn($this->dbMock);

        $ref = new ReflectionClass($this->adapter);
        $method = $ref->getMethod('getTime');
        $method->setAccessible(true);
        $this->adapterTime = $method->invoke($this->adapter);
    }

    public function dataProviderForGet()
    {
        return [
            [false, false],
            [true, false],
            [true, true],
        ];
    }

    /**
     * @dataProvider dataProviderForGet
     *
     * @param bool $hasExecResult
     * @param bool $hasFetchResult
     */
    public function testGet(bool $hasExecResult, bool $hasFetchResult)
    {
        $key = 'testkey1';
        $result = ($hasExecResult && $hasFetchResult) ? ['testres1'] : [];

        $table = $this->dbPrefix . SqlLiteStorageAdapter::TABLE_MAIN;

        $schema = <<<HEREDOC
CREATE TABLE IF NOT EXISTS $table (
  data_key TEXT PRIMARY KEY, 
  data_value BLOB NOT NULL DEFAULT '',
  created_at INTEGER NOT NULL DEFAULT '0'
)
HEREDOC;

        $this->dbMock
            ->expects($this->atLeastOnce())
            ->method('exec')
            ->with($this->equalTo($schema));

        $sql = "SELECT * FROM " . $table . " WHERE data_key = :data_key";

        $queryMock = $this->getMockBuilder(SQLite3Stmt::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept()
            ->getMock();

        $queryMock
            ->expects($this->atLeastOnce())
            ->method('bindValue')
            ->with(
                $this->equalTo('data_key'),
                $this->equalTo($key),
                $this->equalTo(SQLITE3_TEXT)
            );

        $resultMock = $this->getMockBuilder(SQLite3Result::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept()
            ->getMock();

        $queryMock
            ->expects($this->atLeastOnce())
            ->method('execute')
            ->willReturn($hasExecResult ? $resultMock : false);

        $this->dbMock
            ->expects($this->atLeastOnce())
            ->method('prepare')
            ->with($this->equalTo($sql))
            ->willReturn($queryMock);

        if ($hasExecResult) {
            $fatchData = [
                'data_key' => $key,
                'data_value' => serialize($result),
                'created_at' => time(),
            ];

            $resultMock
                ->expects($this->atLeastOnce())
                ->method('fetchArray')
                ->with($this->equalTo(SQLITE3_ASSOC))
                ->willReturn($hasFetchResult ? $fatchData : false);
        } else {
            $resultMock
                ->expects($this->never())
                ->method('fetchArray');
        }

        $this->assertSame($result, $this->adapter->get($key));
    }

    public function getDataProviderForSet()
    {
        return [[false], [true]];
    }

    /**
     * @dataProvider getDataProviderForSet
     *
     * @param bool $execSuccess
     */
    public function testSet(bool $execSuccess)
    {
        $key = 'testkey1';
        $data = ['testdata1'];

        $table = $this->dbPrefix . SqlLiteStorageAdapter::TABLE_MAIN;

        $sql = "REPLACE INTO " . $table . " (data_key,data_value,created_at) " .
            "VALUES (:data_key,:data_value,:created_at)";

        $queryMock = $this->getMockBuilder(SQLite3Stmt::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept()
            ->getMock();

        $queryMock
            ->expects($this->exactly(3))
            ->method('bindValue')
            ->withConsecutive(
                [
                    $this->equalTo('data_key'),
                    $this->equalTo($key),
                    $this->equalTo(SQLITE3_TEXT),
                ],
                [
                    $this->equalTo('data_value'),
                    $this->equalTo(serialize($data)),
                    $this->equalTo(SQLITE3_BLOB),
                ],
                [
                    $this->equalTo('created_at'),
                    $this->equalTo($this->adapterTime),
                    $this->equalTo(SQLITE3_INTEGER),
                ]
            );

        $queryMock
            ->expects($this->atLeastOnce())
            ->method('execute')
            ->willReturn($execSuccess);

        $this->dbMock
            ->expects($this->atLeastOnce())
            ->method('prepare')
            ->with($this->equalTo($sql))
            ->willReturn($queryMock);

        $this->assertSame($execSuccess, $this->adapter->set($key, $data));
    }

    public function getDataProviderForDelete()
    {
        return [
            [
                ['key1', 'key2'],
                [true, true],
                true,
            ],
            [
                ['key3', 'key4', 'key5'],
                [true, false, true],
                false,
            ],
            [
                ['key6', 'key7'],
                [false, false],
                false,
            ],
        ];
    }

    /**
     * @dataProvider getDataProviderForDelete
     *
     * @param array $keys
     * @param array $keySuccess
     * @param bool $overallSuccess
     */
    public function testDelete(array $keys, array $keySuccess, bool $overallSuccess)
    {
        $table = $this->dbPrefix . SqlLiteStorageAdapter::TABLE_MAIN;

        $sql = "DELETE FROM " . $table . " WHERE data_key :data_key";

        $queryMock = $this->getMockBuilder(SQLite3Stmt::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept()
            ->getMock();

        $bindValue = $queryMock
            ->expects($this->exactly(count($keys)))
            ->method('bindValue');
        call_user_func_array(
            [$bindValue, 'withConsecutive'],
            array_map(
                function ($key) {
                    return [
                        $this->equalTo('data_key'),
                        $this->equalTo($key),
                        $this->equalTo(SQLITE3_TEXT),
                    ];
                },
                $keys
            )
        );

        $execute = $queryMock
            ->expects($this->exactly(count($keys)))
            ->method('execute');
        call_user_func_array([$execute, 'willReturnOnConsecutiveCalls'], $keySuccess);

        $this->dbMock
            ->expects($this->atLeastOnce())
            ->method('prepare')
            ->with($this->equalTo($sql))
            ->willReturn($queryMock);

        $this->assertSame($overallSuccess, $this->adapter->delete($keys));
    }

//    public function testGetList($ordering, $hasExecResult, $hasFetchResult)
//    {
//        // TODO
//    }
}
