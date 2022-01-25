<?php

namespace App\Tests\Service;

use App\Controller\Model\Entity\Train;
use App\Controller\Model\Entity\TrainSchedule;
use App\Controller\Model\Entity\TrainStation;
use App\Controller\Model\Repository\ScheduleRepository;
use App\Controller\Model\Repository\TomoDatabaseInterface;
use App\Controller\Model\TrainRepository;
use App\Service\ScheduleService;
use PHPUnit\Framework\TestCase;

class ScheduleServiceTest extends TestCase
{
    /** @var TomoDatabaseInterface */
    private $scheduleRepositoryMock;

    /** @var TomoDatabaseInterface */
    private $trainRepositoryMock;

    /** @var ScheduleService */
    private $scheduleService;

    public function setUp(): void
    {
        parent::setUp();

        $this->scheduleRepositoryMock = $this->createMock(ScheduleRepository::class);
        $this->trainRepositoryMock = $this->createMock(TrainRepository::class);
    }

    public function getService()
    {
        $this->scheduleService = new ScheduleService(
            $this->scheduleRepositoryMock,
            $this->trainRepositoryMock
        );

        $this->assertInstanceOf(ScheduleService::class, $this->scheduleService);

        return $this->scheduleService;
    }

    public function testAddSchedule()
    {
        $trainName = 'tomo';
        $arrivalTimes = ['1030', '2050'];

        $train = new Train(1, $trainName);

        $this->trainRepositoryMock
            ->expects($this->once())
            ->method('get')
            ->willReturn($train);

        $this->scheduleRepositoryMock
            ->expects(self::exactly(count($arrivalTimes)))
            ->method('set');

        $this->getService()->addSchedule($trainName, $arrivalTimes);
    }

    public function testAddScheduleInvalidTrainName()
    {
        $trainName = 'tom';
        $arrivalTimes = ['1030', '2050'];

        $this->expectException(\Exception::class);

        $this->getService()->addSchedule($trainName, $arrivalTimes);
    }

    public function testAddScheduleInvalidTime()
    {
        $trainName = 'tomo';
        $arrivalTimes = ['1030', '2x50'];

        $train = new Train(1, $trainName);

        $this->trainRepositoryMock
            ->expects($this->once())
            ->method('get')
            ->willReturn($train);

        $this->expectException(\Exception::class);

        $this->getService()->addSchedule($trainName, $arrivalTimes);
    }

    public function testGetSchedule()
    {
        $trainName = 'tomo';

        $train = new Train(1, $trainName);
        $station = new TrainStation(1, 'Fulton Street');
        $arrivalTime = '2240';

        $schedules = [new TrainSchedule($train, $station, $arrivalTime)];

        $this->trainRepositoryMock
            ->expects($this->once())
            ->method('get')
            ->willReturn($train);

        $this->scheduleRepositoryMock
            ->expects($this->once())
            ->method('get')
            ->willReturn($schedules);

        $schedules = $this->getService()->getSchedule($trainName);

        $this->assertIsArray($schedules);
    }

    public function testGetScheduleTrainNotFoundException()
    {
        $trainName = 'tomo';

        $this->trainRepositoryMock
            ->expects($this->once())
            ->method('get')
            ->willReturn(null);

        $this->expectException(\Exception::class);

        $this->getService()->getSchedule($trainName);
    }
}
