<?php

namespace App\Controller\Model\Repository;

use App\Controller\Model\Entity\Train;
use App\Controller\Model\Entity\TrainSchedule;
use App\Controller\Model\Entity\TrainStation;


class ScheduleRepository implements TomoDatabaseInterface
{
    /** @var \PDO */
    private $db;

    const DEFAULT_FULTON_STATION_ID = 1;

    /**
     * ScheduleRepository constructor.
     * @param \PDO $db
     */
    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    /**
     * @param string $key
     * @param mixed $val
     * @return mixed
     */
    public function set(string $key, $val)
    {
        $sql = '
	        INSERT INTO schedule (trainId, stationId, arrivalTime)
	        VALUES (:trainId, :stationId, :arrivalTime)
	    ';

        $statement = $this->db->prepare($sql);

        $statement->execute([
            ':trainId' => $key,
            ':stationId' => self::DEFAULT_FULTON_STATION_ID,
            ':arrivalTime' => $val
        ]);
    }

    /**
     * @param string $key - TrainID
     * @return mixed
     */
    public function get(string $key): array
    {
        $sql = '
            SELECT t.id as trainId, t.name as trainName,s.id as stationId, s.name as stationName, arrivalTime
                FROM schedule
                JOIN train t on schedule.trainId = t.id
                JOIN station s on schedule.stationId = s.id
                WHERE t.name = :trainName
            ORDER BY arrivalTime ASC
        ';


        $statement = $this->db->prepare($sql);
        $statement->execute(['trainName' => $key]);
        $statement->setFetchMode(\PDO::FETCH_ASSOC);

        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);

        if (!$result) {
            return [];
        }

        return $this->buildScheduleFromDbResults($result);
    }

    /**
     * @return mixed
     */
    public function keys(): array
    {
        $sql = '
           select a.id as trainId, t.name as trainName, s.id as stationId, s.name as stationName, a.arrivalTime
            FROM schedule a
                JOIN (
                select stationId, arrivalTime, count(*)
                from schedule
                group by stationId, arrivalTime
                having count(*) > 1
            ) b on a.stationId = b.stationId
                AND a.arrivalTime = b.arrivalTime
            JOIN train t on a.trainId = t.id
            JOIN station s on a.stationId = s.id
            order by a.arrivalTime;
        ';

        $statement = $this->db->prepare($sql);
        $statement->execute();
        $statement->setFetchMode(\PDO::FETCH_ASSOC);

        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);

        if (!$result) {
            return [];
        }

        return $this->buildScheduleFromDbResults($result);
    }

    /**
     * @param array $associativeArraySchedules
     * @return TrainSchedule[]
     */
    public function buildScheduleFromDbResults(array $associativeArraySchedules): array
    {
        $scheduleObjects = [];

        foreach ($associativeArraySchedules as $associativeArraySchedule) {
            $train = new Train($associativeArraySchedule['trainId'], $associativeArraySchedule['trainName']);
            $station = new TrainStation($associativeArraySchedule['stationId'], $associativeArraySchedule['stationName']);

            $scheduleObjects[] = new TrainSchedule($train, $station, $associativeArraySchedule['arrivalTime']);
        }

        return $scheduleObjects;
    }
}
