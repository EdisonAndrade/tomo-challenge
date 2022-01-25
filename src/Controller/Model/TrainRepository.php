<?php

namespace App\Controller\Model;

use App\Controller\Model\Entity\Train;
use App\Controller\Model\Repository\TomoDatabaseInterface;

class TrainRepository implements TomoDatabaseInterface
{
    /** @var \PDO */
    private $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    /** implemented */
    public function set(string $key, $val)
    {
        throw new \Exception('Not implemented for adding new trains');
    }

    /** implemented */
    public function get(string $key)
    {
        $sql = 'select id, name from train WHERE name = :trainName LIMIT 1;';

        $statement = $this->db->prepare($sql);
        $statement->execute(['trainName' => $key]);
        $statement->setFetchMode(\PDO::FETCH_ASSOC);

        $trainData = $statement->fetch();
        if (!$trainData) {
            return null;
        }

        return new Train((int)$trainData['id'], $trainData['name']);
    }

    /** implemented */
    public function keys(): array
    {
        $sql = 'select * from train;';

        $statement = $this->db->prepare($sql);
        $statement->setFetchMode(\PDO::FETCH_CLASS, Train::class);

        $trains = $statement->fetchAll();
        if (!$trains) {
            return [];
        }

        return $trains;
    }
}
