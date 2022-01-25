<?php

namespace App\Service;

use App\Controller\Model\Entity\Train;
use App\Controller\Model\Entity\TrainSchedule;
use App\Controller\Model\Repository\TomoDatabaseInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ScheduleService
{
    const TRAIN_IDENTIFIER_MAX_LENGTH = 4;
    const ARRIVAL_TIME_LENGTH = 4;
    const MAX_NUMBER_OF_MINUTES = 60;
    const MAX_NUMBER_OF_HOURS = 23;

    /** @var TomoDatabaseInterface */
    private $scheduleRepository;

    /** @var TomoDatabaseInterface */
    private $trainRepository;

    public function __construct(TomoDatabaseInterface $scheduleRepository, TomoDatabaseInterface $trainRepository)
    {
        $this->scheduleRepository = $scheduleRepository;
        $this->trainRepository = $trainRepository;
    }

    public function addSchedule(string $trainId, array $arrivalTimes)
    {
        // before making DB call, make sure train identifier is 4-chars in lenght
        if (strlen(trim($trainId)) !== self::TRAIN_IDENTIFIER_MAX_LENGTH) {
            throw new \Exception('An invalid train identifier has been submitted.');
        }

        /** @var Train $train */
        $train = $this->trainRepository->get($trainId);

        if ($train === null) {
            throw new NotFoundHttpException('Train not found');
        }

        $this->validateArrivalTimes($trainId, $arrivalTimes);

        // insert new schedules

        foreach ($arrivalTimes as $arrivalTime) {
            $sqlTime = $this->getFormattedArrivalTime($arrivalTime);
            $this->scheduleRepository->set($train->getId(), $sqlTime);
        }
    }

    /**
     * @param string $trainId - The train 4-digit identifier
     */
    public function getSchedule(string $trainId)
    {
        // before making DB call, make sure train identifier is 4-chars in lenght
        if (strlen(trim($trainId)) !== self::TRAIN_IDENTIFIER_MAX_LENGTH) {
            throw new \Exception('An invalid train identifier has been submitted.');
        }

        /** @var Train $train */
        $train = $this->trainRepository->get($trainId);

        if ($train === null) {
            throw new NotFoundHttpException('Train not found');
        }

        $schedules = $this->scheduleRepository->get($train->getName());

        $wrappedAroundSchedules = $this->wrapSchedules($schedules);

        return $wrappedAroundSchedules;
    }

    public function getNextSchedule()
    {
        $schedules = $this->scheduleRepository->keys();

        return $this->wrapSchedules($schedules);
    }

    /**
     * Validates all provided arrival times in military time.
     * @param string $trainName
     * @param string $stationName
     * @param string[] $arrivalTimes
     * @throws \Exception
     */
    private function validateArrivalTimes(string $trainName, array $arrivalTimes): void
    {
        if (empty($arrivalTimes)) {
            throw new \Exception('Arrival times list is empty.');
        }

        // loop through each arrival time and make sure they are in the format of mmhh
        foreach ($arrivalTimes as $arrivalTime) {
            if (strlen($arrivalTime) !== self::ARRIVAL_TIME_LENGTH) {
                throw new \Exception('Invalid arrival time provided.');
            }

            if (!is_numeric($arrivalTime)) {
                throw new \Exception('Invalid arrival time provided. Must be numeric in hhmm format.');
            }

            // extract the hour and minutes from arrival time.
            $hour = substr($arrivalTime, 0, 2); // first two chars
            if ((int)$hour > self::MAX_NUMBER_OF_HOURS || (int)$hour < 0) {
                throw new \Exception(
                    'Invalid number of hours provided. Must be between 0 and 23. ' . $hour . ' provided'
                );
            }

            $minutes = substr($arrivalTime, 2, 3); // last two chars

            if ((int)$minutes > self::MAX_NUMBER_OF_MINUTES || (int)$minutes < 0) {
                throw new \Exception(
                    'Invalid number of minutes provided. Must be between 0 and 60. ' . $minutes . ' provided'
                );
            }

            if ($this->trainArrivalTimeExists($trainName, $arrivalTime)) {
                throw new \Exception('Train ' . $trainName . ' is already scheduled daily for ' . $arrivalTime);
            }
        }

        // at this point, all arrival times have been validated
    }


    /**
     * @param string $trainName
     * @param string $stationName
     * @param string $arrivalTime
     * @return bool
     */
    private function trainArrivalTimeExists(string $trainName, string $arrivalTime): bool
    {
        /** @var TrainSchedule[] $shedules */
        $schedules = $this->scheduleRepository->get($trainName);

        if (!$schedules || empty($schedules)) {
            return false;
        }

        /** @var TrainSchedule $schedule */
        foreach ($schedules as $schedule) {
            $train = $schedule->getTrain();
            if ($train->getName() === $trainName && $schedule->getArrivalTime() === $this->getFormattedArrivalTime($arrivalTime)) {
                return true; // a train scheduled for $arrivalTime already exist for train $trainName at this $stationName
            }
        }

        return false; // default
    }

    /**
     * @param string $arrivalTime
     * @return string
     */
    private function getFormattedArrivalTime(string $arrivalTime): string
    {
        return substr_replace($arrivalTime, ':', 2, 0) . ':00'; // add seconds too
    }

    /**
     * @param TrainSchedule $schedules
     * @return TrainSchedule[]
     */
    private function wrapSchedules(array $schedules): array
    {
        if (count($schedules) === 1) {
            return $schedules;
        }

        $now = time(); // current time. schedules before this time will wrap around to next day

        // first, get the time that is closest to current time

        $upcomingTrainSchedules = [];
        $expiredTrainSchedules = [];
        for ($i = 0; $i < count($schedules); $i++) {
            /** @var TrainSchedule $schedule */
            $schedule = $schedules[$i];
            $arrivalTime = strtotime($schedule->getArrivalTime());

            if ($arrivalTime >= $now) {
                $upcomingTrainSchedules = array_slice($schedules, $i);
                $expiredTrainSchedules = array_slice($schedules, 0, $i);
                break;
            }

            if ($i === count($schedules) - 1) {
                // we've reached the end, and all schedules are expired
                $expiredTrainSchedules = $schedules;
            }
        }

        return array_merge($upcomingTrainSchedules, $expiredTrainSchedules);
    }
}
