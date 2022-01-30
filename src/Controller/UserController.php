<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\ActivityRepository;
use App\Service\ObjectSerializer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Throwable;

/**
 * @Route("/users", name="users_")
 */
class UserController extends BaseController
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
    public function listUserActivities(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();

            // Get user activities
            $activityEntities = $this->activityRepository->findActivitiesByUser($user);

            // Build and return response
            $activities = $this->serializer->normalize($activityEntities, null, [AbstractNormalizer::IGNORED_ATTRIBUTES => ['users']]);
            $message = sprintf('Found %d activities', count($activities));
            return $this->buildResponse(Response::HTTP_OK, $message, $activities);

        } catch (Throwable $e) {
            return $this->buildResponse(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    /**
     * @Route("/activities/{id}", methods={"POST"}, name="activities_join")
     */
    public function joinActivity(int $id): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();

            $activityEntity = $this->activityRepository->findAvailable($id)
                ?? throw new EntityNotFoundException();

            $activityEntity->joinUser($user);
            $this->entityManager->flush();

            return $this->buildResponse(Response::HTTP_OK, 'Joined activity');

        } catch (NoAvailableSeatsLeftException | UserAlreadyHasSeatException $e) {
            return $this->buildResponse(Response::HTTP_BAD_REQUEST, $e->getMessage());

        } catch (EntityNotFoundException $e) {
            return $this->buildResponse(Response::HTTP_NOT_FOUND, 'Activity not found');

        } catch (Throwable $e) {
            return $this->buildResponse(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    /**
     * @Route("/activities/{id}", methods={"DELETE"}, name="activities_leave")
     */
    public function leaveActivity(int $id): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();

            $activityEntity = $this->activityRepository->find($id)
                ?? throw new EntityNotFoundException();

            $activityEntity->leaveUser($user);
            $this->entityManager->flush();

            return $this->buildResponse(Response::HTTP_OK, 'Left activity');

        } catch (EntityNotFoundException $e) {
            return $this->buildResponse(Response::HTTP_NOT_FOUND, 'Activity not found');

        } catch (Throwable $e) {
            return $this->buildResponse(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }
}
