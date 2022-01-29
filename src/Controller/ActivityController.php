<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Model\DataObject;
use App\Repository\ActivityRepository;
use App\Service\ObjectSerializer;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

class ActivityController extends BaseController
{
    public function __construct(
        private ActivityRepository     $activityRepository,
        private EntityManagerInterface $entityManager,
        private ObjectSerializer       $serializer,
    )
    {
    }

    /**
     * @Route("/activities", methods={"GET"}, name="activities_list")
     */
    public function listActivities(): JsonResponse
    {
        try {
            // Get all activities
            $activityEntities = $this->activityRepository->findAll();

            // Build and return response
            $activities = $this->serializer->normalize($activityEntities);
            $message = sprintf('Found %d activities', count($activities));
            return $this->buildResponse(Response::HTTP_OK, $message, $activities);

        } catch (Throwable $e) {
            return $this->buildResponse(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    /**
     * @Route("/activities/{id}", methods={"GET"}, name="activities_get")
     */
    public function getActivity(int $id): JsonResponse
    {
        try {
            // Search the activity by ID; throws an exception if not found
            $activityEntity = $this->activityRepository->find($id)
                ?? throw new EntityNotFoundException();

            // Activity found
            $activity = $this->serializer->normalize($activityEntity);
            return $this->buildResponse(Response::HTTP_OK, 'Activity found', $activity);

        } catch (EntityNotFoundException) {
            return $this->buildResponse(Response::HTTP_NOT_FOUND, 'Activity not found');

        } catch (Throwable $e) {
            return $this->buildResponse(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    /**
     * @Route("/activities", methods={"POST"}, name="activities_create")
     */
    public function createActivity(Request $request): JsonResponse
    {
        try {
            // Get request data
            $data = $this->getRequestBody($request);

            // Build a new activity
            $activity = new Activity();
            $this->fillActivityWithData($activity, $data);

            // Persist the activity
            $this->entityManager->persist($activity);
            $this->entityManager->flush();

            // Build and return response
            return $this->buildResponse(Response::HTTP_OK, 'Activity created', ['id' => $activity->getId()]);

        } catch (InvalidRequestBodyException | MissingRequiredFieldException $e) {
            return $this->buildResponse(Response::HTTP_BAD_REQUEST, $e->getMessage());

        } catch (Throwable $e) {
            return $this->buildResponse(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    /**
     * @Route("/activities/{id}", methods={"PUT"}, name="activities_edit")
     */
    public function editActivity(int $id, Request $request): JsonResponse
    {
        try {
            // Get request data
            $data = $this->getRequestBody($request);

            // Search the activity by ID; throws an exception if not found
            $activity = $this->activityRepository->find($id)
                ?? throw new EntityNotFoundException();

            // Update the activity
            $this->fillActivityWithData($activity, $data);
            $this->entityManager->flush();

            // Build and return response
            return $this->buildResponse(Response::HTTP_OK, 'Activity edited');

        } catch (InvalidRequestBodyException | MissingRequiredFieldException $e) {
            return $this->buildResponse(Response::HTTP_BAD_REQUEST, $e->getMessage());

        } catch (EntityNotFoundException) {
            return $this->buildResponse(Response::HTTP_NOT_FOUND, 'Activity not found');

        } catch (Throwable $e) {
            return $this->buildResponse(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    /**
     * @Route("/activities/{id}", methods={"DELETE"}, name="activities_delete")
     */
    public function deleteActivity(int $id): JsonResponse
    {
        try {
            // Search the activity by ID; throws an exception if not found
            $activity = $this->activityRepository->find($id)
                ?? throw new EntityNotFoundException();

            // Delete the activity
            $this->entityManager->remove($activity);
            $this->entityManager->flush();

            // Build and return response
            return $this->buildResponse(Response::HTTP_OK, 'Activity deleted');

        } catch (EntityNotFoundException) {
            return $this->buildResponse(Response::HTTP_NOT_FOUND, 'Activity not found');

        } catch (Throwable $e) {
            return $this->buildResponse(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    /**
     * @param Activity $activity
     * @param DataObject $data
     * @throws MissingRequiredFieldException
     */
    private function fillActivityWithData(Activity $activity, DataObject $data)
    {
        $activity->setName($data->getRequired('name'));
        $activity->setLocation($data->getRequired('location'));
        $activity->setStartAt(DateTime::createFromFormat(DATE_ATOM, $data->getRequired('startAt')));
        $activity->setEndAt(DateTime::createFromFormat(DATE_ATOM, $data->getRequired('endAt')));
        $activity->setAvailableSeats($data->getRequired('availableSeats'));
        $activity->setOccupiedSeats($data->getRequired('occupiedSeats'));
    }
}
