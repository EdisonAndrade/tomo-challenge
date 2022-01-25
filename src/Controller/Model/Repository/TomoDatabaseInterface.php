<?php

namespace App\Controller\Model\Repository;

interface TomoDatabaseInterface
{
    /**
     * Tomo objective: Implement a function to store a value for a key
     *
     * @param string $key
     * @param mixed $val
     * @return mixed
     */
    public function set(string $key, $val);

    /**
     * Tomo objective: Implement a function to return a value given a key
     *
     * @param string $key
     * @return mixed
     */
    public function get(string $key);

    /**
     * Tomo objective: Implement a function to return database keys
     */
    public function keys();
}
