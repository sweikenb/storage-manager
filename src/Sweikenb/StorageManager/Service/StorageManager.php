<?php
/**
 * @copyright Copyright (c) 2017 Simon Schröer <https://schroeer.me>
 *
 * @see LICENSE
 */

namespace Sweikenb\StorageManager\Service;

use Sweikenb\StorageManager\Abstracts\AbstractStorageManager;
use Sweikenb\StorageManager\Api\StorageAdapterInterface;
use Sweikenb\StorageManager\Api\StorageManagerInterface;

class StorageManager extends AbstractStorageManager implements StorageManagerInterface
{
    /**
     * StorageManager constructor.
     *
     * @param \Sweikenb\StorageManager\Api\StorageAdapterInterface|null $storageAdapter
     */
    public function __construct(StorageAdapterInterface $storageAdapter = null)
    {
        if (null !== $storageAdapter) {
            $this->setStorageAdapter($storageAdapter);
        }
    }

    /**
     * @return array
     */
    public function getOldestItem(): array
    {
        return $this->storageAdapter->getList(1, 0, self::ORDER_ASC);
    }

    /**
     * @return array
     */
    public function getNewestItem(): array
    {
        return $this->storageAdapter->getList(1, 0, self::ORDER_DESC);
    }
}
