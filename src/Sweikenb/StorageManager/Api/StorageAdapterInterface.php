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
interface StorageAdapterInterface
{
    const ORDER_ASC = 1;
    const ORDER_DESC = 2;

    /**
     * @param string $key
     *
     * @return array
     */
    public function get(string $key): array;

    /**
     * @param string $key
     * @param array $value
     *
     * @return bool
     */
    public function set(string $key, array $value): bool;

    /**
     * @param array $keys
     *
     * @return bool
     */
    public function delete(array $keys): bool;

    /**
     * @param int $limit
     * @param int|null $offset
     * @param int|null $ordering
     *
     * @return array
     */
    public function getList(int $limit, int $offset = null, int $ordering = null): array;

    /**
     * @param int|null $ordering
     *
     * @return array
     */
    public function getAll(int $ordering = null): array;
}
