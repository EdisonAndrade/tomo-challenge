<?php

namespace App\Controller\Model\Entity;

class TrainSchedule
{
    /** @var int */
    private $id;

    /** @var Train */
    private $train;

    /** @var TrainStation */
    private $trainStation;

    /** @var string */
    private $arrivalTime;

    public function __construct(Train $train, TrainStation $trainStation, string $arrivalTime)
    {
        $this->train = $train;
        $this->trainStation = $trainStation;
        $this->arrivalTime = $arrivalTime;
    }

    /**
     * @return Train
     */
    public function getTrain(): Train
    {
        return $this->train;
    }

    /**
     * @param Train $train
     * @return TrainSchedule
     */
    public function setTrain(Train $train): self
    {
        $this->train = $train;
        return $this;
    }

    /**
     * @return TrainStation
     */
    public function getTrainStation(): TrainStation
    {
        return $this->trainStation;
    }

    /**
     * @param TrainStation $trainStation
     * @return TrainSchedule
     */
    public function setTrainStation(TrainStation $trainStation): self
    {
        $this->trainStation = $trainStation;
        return $this;
    }

    /**
     * @return string
     */
    public function getArrivalTime(): string
    {
        return $this->arrivalTime;
    }

    /**
     * @param string $arrivalTime
     * @return TrainSchedule
     */
    public function setArrivalTime(string $arrivalTime): self
    {
        $this->arrivalTime = $arrivalTime;
        return $this;
    }
}
