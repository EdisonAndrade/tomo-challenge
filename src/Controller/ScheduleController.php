<?php

namespace App\Controller;

use App\Service\ScheduleService;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class ScheduleController
{
    const LOG_TYPE_ERROR = 'error';
    const LOG_TYPE_INFO = 'info';

    /** @var ScheduleService */
    private $scheduleService;

    /** @var SerializerInterface */
    private $serializer;

    public function __construct(ScheduleService $scheduleService, SerializerInterface $serializer)
    {
        $this->scheduleService = $scheduleService;
        $this->serializer = $serializer;
    }

    /**
     * Tomo objective: Implement a route that adds a train line
     *
     * @return JsonResponse
     */
    public function addTrain(\Symfony\Component\HttpFoundation\Request $request): JsonResponse
    {
        // Extract [POST] request data, default to null if missing
        $requestBody = $request->getContent();
        $requestData = json_decode($requestBody, true);

        try {
            $trainName = $requestData['trainName'] ?? null;
            $arrivalTimes = $requestData['arrivalTimes'] ?? null;

            if (!$trainName || !$arrivalTimes) {
                $this->log(
                    self::LOG_TYPE_ERROR,
                    'Missing required data',
                    $requestData
                );

                throw new BadRequestException('Train name and arrival times are required fields.');
            }

            $this->scheduleService->addSchedule($trainName, $arrivalTimes);
        } catch (\Exception $e) {
            $this->log(
                self::LOG_TYPE_ERROR,
                $e->getMessage()
            );

            // return error response
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(); // defaults to 200 OK response
    }

    /**
     * Tomo objective: Implement a route that returns the schedule for a given train line
     *
     * @return JsonResponse
     */
    public function getSchedule(string $trainId): JsonResponse
    {
        try {
            $schedule = $this->scheduleService->getSchedule($trainId);
        } catch (\Exception $e) {
            $this->log(
                self::LOG_TYPE_ERROR,
                $e->getMessage()
            );

            // return error response
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse($this->serializer->normalize([
            [
                'count' => count($schedule),
                'items' => $schedule
            ]
        ]));
    }

    /**
     * tomo objective: Implement a route that returns the next time multiple trains are in the station
     *
     * @return JsonResponse
     */
    public function getNext(): JsonResponse
    {
        try {
            $schedules = $this->scheduleService->getNextSchedule();
        } catch (\Exception $e) {
            $this->log(
                self::LOG_TYPE_ERROR,
                $e->getMessage()
            );

            // return error response
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse($this->serializer->normalize(
            [
                'count' => count($schedules),
                'items' => $schedules
            ]
        ));
    }

    /**
     * This is a function that will log user errors and interactions.
     * @param string $type
     * @param string $message
     * @param string[]|null $context
     */
    private function log(string $type, string $message, array $context = null): void
    {
        /*
        For this exercise, let's just pretend that this function writes to a log file. Ideally this function
        will live in its own dedicated service.
        */
    }
}
