<?php
/**
 * @copyright Copyright (c) 2017 Simon Schröer <https://schroeer.me>
 *
 * @see LICENSE
 */

namespace Sweikenb\StorageManager\Adapter;

use SQLite3;
use SQLite3Stmt;
use Sweikenb\StorageManager\Api\StorageAdapterInterface;

/**
 * Class SqlLiteStorageAdapter
 *
 * @package Sweikenb\StorageManager\Adapter
 */
class SqlLiteStorageAdapter implements StorageAdapterInterface
{
    const TABLE_MAIN = 'sweikenb_storagemanager';

    /**
     * @var string
     */
    private $sqliteDbFile;

    /**
     * @var SQLite3|null
     */
    private $connection;

    /**
     * @var null|string
     */
    private $tablePrefix;

    /**
     * SqlLiteStorageAdapter constructor.
     *
     * @param string $sqliteDbFile
     * @param string|null $tablePrefix
     */
    public function __construct(string $sqliteDbFile, string $tablePrefix = null)
    {
        $this->sqliteDbFile = $sqliteDbFile;
        $this->tablePrefix = $tablePrefix;
    }

    /**
     * @param string $filename
     *
     * @return \SQLite3
     * @codeCoverageIgnore
     */
    protected function getFreshSqliteInstance(string $filename): SQLite3
    {
        return new SQLite3($filename);
    }

    /**
     * @return SQLite3
     */
    protected function getConnection(): SQLite3
    {
        if (!$this->connection) {
            $this->connection = $this->getFreshSqliteInstance($this->sqliteDbFile);
            $this->createSchemaIfNotExists($this->connection);
        }

        return $this->connection;
    }

    /**
     * @param \SQLite3 $db
     *
     * @return void
     */
    protected function createSchemaIfNotExists(SQLite3 $db)
    {
        $mainTable = $this->getTableName(self::TABLE_MAIN);

        // define the required schema
        $schema = <<<HEREDOC
CREATE TABLE IF NOT EXISTS $mainTable (
  data_key TEXT PRIMARY KEY, 
  data_value BLOB NOT NULL DEFAULT '',
  created_at INTEGER NOT NULL DEFAULT '0'
)
HEREDOC;

        // execute the schema
        $db->exec($schema);
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function getTableName(string $name): string
    {
        if (null !== $this->tablePrefix) {
            $name = $this->tablePrefix . $name;
        }

        return $name;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function extractDataValue(array $data): array
    {
        return isset($data['data_value'])
            ? (array)unserialize($data['data_value'])
            : [];
    }

    /**
     * @param \SQLite3Stmt $query
     *
     * @return array
     */
    protected function fetchResults(SQLite3Stmt $query): array
    {
        $result = $query->execute();
        if (!$result) {
            return [];
        }

        $results = [];
        while ($data = $result->fetchArray(SQLITE3_ASSOC)) {
            $results[] = $this->extractDataValue($data);
        }

        return $results;
    }

    /**
     * @param int|null $ordering
     *
     * @return string
     */
    protected function resolveOrderBy(int $ordering = null): string
    {
        return self::ORDER_DESC === $ordering
            ? 'DESC'
            : 'ASC';
    }

    /**
     * @return int
     */
    protected function getTime(): int
    {
        static $time;
        if (!$time) {
            $time = time();
        }

        return $time;
    }

    /**
     * @param string $key
     *
     * @return array
     */
    public function get(string $key): array
    {
        $query = $this->getConnection()->prepare(
            "SELECT * FROM " . $this->getTableName(self::TABLE_MAIN) .
            " WHERE data_key = :data_key"
        );
        $query->bindValue('data_key', $key, SQLITE3_TEXT);

        $result = $query->execute();
        if (!$result) {
            return [];
        }

        $data = $result->fetchArray(SQLITE3_ASSOC);
        if (!$data) {
            return [];
        }

        return $this->extractDataValue($data);
    }

    /**
     * @param string $key
     * @param array $value
     *
     * @return bool
     */
    public function set(string $key, array $value): bool
    {
        $query = $this->getConnection()->prepare(
            "REPLACE INTO " . $this->getTableName(self::TABLE_MAIN) . " (data_key,data_value,created_at) " .
            "VALUES (:data_key,:data_value,:created_at)"
        );
        $query->bindValue('data_key', $key, SQLITE3_TEXT);
        $query->bindValue('data_value', serialize($value), SQLITE3_BLOB);
        $query->bindValue('created_at', $this->getTime(), SQLITE3_INTEGER);

        return !!$query->execute();
    }

    /**
     * @param array $keys
     *
     * @return bool
     */
    public function delete(array $keys): bool
    {
        $status = true;
        foreach ($keys as $key) {
            $query = $this->getConnection()->prepare(
                "DELETE FROM " . $this->getTableName(self::TABLE_MAIN) . " WHERE data_key :data_key"
            );
            $query->bindValue('data_key', $key, SQLITE3_TEXT);
            if (!$query->execute()) {
                $status = false;
            }
        }

        return $status;
    }

    /**
     * @param int $limit
     * @param int|null $offset
     * @param int|null $ordering
     *
     * @return array
     */
    public function getList(int $limit, int $offset = null, int $ordering = null): array
    {
        if (null === $offset) {
            $offset = 0;
        }

        $query = $this->getConnection()->prepare(
            "SELECT * FROM " . $this->getTableName(self::TABLE_MAIN) .
            " ORDER BY created_at " . $this->resolveOrderBy($ordering) . " LIMIT :offset, :limit"
        );

        $query->bindValue('offset', $offset, SQLITE3_INTEGER);
        $query->bindValue('limit', $limit, SQLITE3_INTEGER);

        return $this->fetchResults($query);
    }

    /**
     * @param int|null $ordering
     *
     * @return array
     */
    public function getAll(int $ordering = null): array
    {
        $query = $this->getConnection()->prepare(
            "SELECT * FROM " . $this->getTableName(self::TABLE_MAIN) .
            " ORDER BY created_at " . $this->resolveOrderBy($ordering)
        );

        return $this->fetchResults($query);
    }
}
