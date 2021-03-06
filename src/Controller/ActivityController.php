<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Exception\InvalidFieldException;
use App\Model\DataObject;
use App\Repository\ActivityImageRepository;
use App\Repository\ActivityRepository;
use App\Repository\CategoryRepository;
use App\Service\ActivityMediaService;
use App\Service\ObjectSerializer;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Throwable;

class ActivityController extends BaseController
{
    public function __construct(
        private ActivityRepository      $activityRepository,
        private CategoryRepository      $categoryRepository,
        private ActivityImageRepository $activityImageRepository,
        private EntityManagerInterface  $entityManager,
        private ObjectSerializer        $serializer,
        private ActivityMediaService    $activityMediaService,
    )
    {
    }

    /**
     * @Route("/activities", methods={"GET"}, name="activities_list")
     */
    public function listActivities(Request $request): JsonResponse
    {
        try {
            $filters = $this->getFilters($request);

            // Get all activities by filters
            $activityEntities = $this->activityRepository->findAllByFilter(
                $filters['name'],
                $filters['day'],
                $filters['availableOnly'],
            );

            // Build and return response
            $activities = $this->serializer->normalize($activityEntities, null, [
                AbstractNormalizer::GROUPS => 'activity',
                AbstractNormalizer::IGNORED_ATTRIBUTES => ['users'],
            ]);
            $message = sprintf('Found %d activities', count($activities));
            return $this->buildResponse(Response::HTTP_OK, $message, $activities);

        } catch (Throwable $e) {
            return $this->buildResponse(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    /**
     * @Route("/activities/{id}", methods={"GET"}, name="activities_get", requirements={"id"="\d+"})
     */
    public function getActivity(int $id): JsonResponse
    {
        try {
            // Search the activity by ID; throws an exception if not found
            $activityEntity = $this->activityRepository->find($id)
                ?? throw new EntityNotFoundException('Activity not found');

            // Activity found
            $activity = $this->serializer->normalize($activityEntity, null, [
                AbstractNormalizer::GROUPS => 'activity',
                AbstractNormalizer::IGNORED_ATTRIBUTES => ['users'],
            ]);
            return $this->buildResponse(Response::HTTP_OK, 'Activity found', $activity);

        } catch (EntityNotFoundException $e) {
            return $this->buildResponse(Response::HTTP_NOT_FOUND, $e->getMessage());

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
     * @Route("/activities/{id}", methods={"PUT"}, name="activities_edit", requirements={"id"="\d+"})
     */
    public function editActivity(int $id, Request $request): JsonResponse
    {
        try {
            // Get request data
            $data = $this->getRequestBody($request);

            // Search the activity by ID; throws an exception if not found
            $activity = $this->activityRepository->find($id)
                ?? throw new EntityNotFoundException('Activity not found');

            // Update the activity
            $this->fillActivityWithData($activity, $data);
            $this->entityManager->flush();

            // Build and return response
            return $this->buildResponse(Response::HTTP_OK, 'Activity edited');

        } catch (InvalidRequestBodyException | MissingRequiredFieldException $e) {
            return $this->buildResponse(Response::HTTP_BAD_REQUEST, $e->getMessage());

        } catch (EntityNotFoundException $e) {
            return $this->buildResponse(Response::HTTP_NOT_FOUND, $e->getMessage());

        } catch (Throwable $e) {
            return $this->buildResponse(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    /**
     * @Route("/activities/{id}", methods={"DELETE"}, name="activities_delete", requirements={"id"="\d+"})
     */
    public function deleteActivity(int $id): JsonResponse
    {
        try {
            // Search the activity by ID; throws an exception if not found
            $activity = $this->activityRepository->find($id)
                ?? throw new EntityNotFoundException('Activity not found');

            // Delete the activity
            $this->entityManager->remove($activity);
            $this->entityManager->flush();

            // Build and return response
            return $this->buildResponse(Response::HTTP_OK, 'Activity deleted');

        } catch (EntityNotFoundException $e) {
            return $this->buildResponse(Response::HTTP_NOT_FOUND, $e->getMessage());

        } catch (Throwable $e) {
            return $this->buildResponse(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    #[ArrayShape(['name' => "string|null", 'day' => "DateTime|null", 'availableOnly' => "bool"])]
    private function getFilters(Request $request): array
    {
        $query = $request->query;

        $name = $query->get('name');
        $day = $query->get('day') != null
            ? DateTime::createFromFormat('Y-m-d', $query->get('day'))
            : null;
        $availableOnly = $query->getBoolean('availableOnly');

        return [
            'name' => $name,
            'day' => $day,
            'availableOnly' => $availableOnly,
        ];
    }

    /**
     * @param Activity $activity
     * @param DataObject $data
     * @throws MissingRequiredFieldException
     * @throws EntityNotFoundException
     * @throws InvalidFieldException
     */
    private function fillActivityWithData(Activity $activity, DataObject $data)
    {
        $activity->setName($data->getRequired('name'));
        $activity->setLocation($data->getRequired('location'));
        $activity->setStartAt(DateTime::createFromFormat(DATE_ATOM, $data->getRequired('startAt')));
        $activity->setEndAt(DateTime::createFromFormat(DATE_ATOM, $data->getRequired('endAt')));
        $activity->setAvailableSeats($data->getRequired('availableSeats'));

        // If media is set, link activity to the media
        $media = $data->get('media');
        if ($media != null) {
            $this->handleSetMedia($activity, $media);
        }

        // Add categories to activity
        $categories = $data->getRequired('categories');
        if (!is_array($categories)) {
            throw new InvalidFieldException('Field `categories` should be an array of integer ID');
        }

        $this->handleSetCategories($activity, $categories);
    }

    /**
     * @param Activity $activity
     * @param int $media
     * @throws EntityNotFoundException
     */
    private function handleSetMedia(Activity $activity, int $media)
    {
        // Get activity media
        $activityMedia = $this->activityImageRepository->find($media)
            ?? throw new EntityNotFoundException('Media not found with ID: ' . $media);

        $activity->setMedia($activityMedia);
    }

    /**
     * @param Activity $activity
     * @param array $categories
     * @throws EntityNotFoundException
     */
    private function handleSetCategories(Activity $activity, array $categories)
    {
        $activity->clearCategories();

        foreach ($categories as $category) {
            // Finds category entity and adds it to the activity
            $categoryEntity = $this->categoryRepository->find($category)
                ?? throw new EntityNotFoundException('Category not found with ID: ' . $category);

            $activity->addCategory($categoryEntity);
        }
    }
}
