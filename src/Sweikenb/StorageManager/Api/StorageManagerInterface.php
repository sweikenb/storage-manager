<?php
/**
 * @copyright Copyright (c) 2017 Simon Schröer <https://schroeer.me>
 *
 * @see LICENSE
 */

namespace Sweikenb\StorageManager\Api;

/**
 * @api
 */
interface StorageManagerInterface extends StorageAdapterInterface
{
    /**
     * @param \Sweikenb\StorageManager\Api\StorageAdapterInterface $storageAdapter
     *
     * @return void
     */
    public function setStorageAdapter(StorageAdapterInterface $storageAdapter);

    /**
     * @return array
     */
    public function getOldestItem(): array;

    /**
     * @return array
     */
    public function getNewestItem(): array;
}
