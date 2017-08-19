<?php
/**
 * @copyright Copyright (c) 2017 Simon Schröer <https://schroeer.me>
 *
 * @see LICENSE
 */

namespace Sweikenb\StorageManager\Abstracts;

use Sweikenb\StorageManager\Api\StorageAdapterInterface;
use Sweikenb\StorageManager\Api\StorageManagerInterface;

abstract class AbstractStorageManager implements StorageManagerInterface
{
    /**
     * @var StorageAdapterInterface
     */
    protected $storageAdapter;

    /**
     * @param \Sweikenb\StorageManager\Api\StorageAdapterInterface $storageAdapter
     *
     * @return void
     */
    public function setStorageAdapter(StorageAdapterInterface $storageAdapter)
    {
        $this->storageAdapter = $storageAdapter;
    }

    /**
     * @param string $key
     *
     * @return array
     */
    public function get(string $key): array
    {
        return $this->storageAdapter->get($key);
    }

    /**
     * @param string $key
     * @param array $value
     *
     * @return bool
     */
    public function set(string $key, array $value): bool
    {
        return $this->storageAdapter->set($key, $value);
    }

    /**
     * @param array $keys
     *
     * @return bool
     */
    public function delete(array $keys): bool
    {
        return $this->storageAdapter->delete($keys);
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
        return $this->storageAdapter->getList($limit, $offset, $ordering);
    }

    /**
     * @param int|null $ordering
     *
     * @return array
     */
    public function getAll(int $ordering = null): array
    {
        return $this->storageAdapter->getAll($ordering);
    }
}
