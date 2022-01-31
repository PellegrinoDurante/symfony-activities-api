<?php

namespace App\Controller;

use App\Entity\ActivityMedia;
use App\Repository\ActivityRepository;
use App\Service\ActivityMediaService;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

class ActivityMediaController extends BaseController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ActivityRepository     $activityRepository,
        private ActivityMediaService   $activityMediaService,
    )
    {
    }

    /**
     * @Route("/media", methods={"POST"}, name="activities_media_create")
     */
    public function createActivityMedia(Request $request): JsonResponse
    {
        try {
            // Store file in media directory
            /** @var UploadedFile $activityMediaFile */
            $activityMediaFile = $request->files->get('media')
                ?? throw new MissingRequiredFieldException('media');

            $extension = $activityMediaFile->guessExtension() ?? $activityMediaFile->getExtension();

            $activityMediaFileName = Uuid::uuid4() . "." . $extension;
            $activityMediaFile->move($this->activityMediaService->getDirectory(), $activityMediaFileName);

            // Create a new entity and return its ID
            $activityImage = new ActivityMedia();
            $activityImage->setFilename($activityMediaFileName);

            $this->entityManager->persist($activityImage);
            $this->entityManager->flush();

            return $this->buildResponse(Response::HTTP_OK, 'Activity media uploaded', ['id' => $activityImage->getId()]);

        } catch (Throwable $e) {
            return $this->buildResponse(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    /**
     * @Route("/activities/{activityId}/media", methods={"GET"}, name="activities_media_get", requirements={"activityId"="\d+"})
     */
    public function getActivityMedia(int $activityId): Response
    {
        try {
            // Search the activity by ID; throws an exception if not found
            $activityEntity = $this->activityRepository->find($activityId)
                ?? throw new EntityNotFoundException('Activity not found');

            // Activity found
            $filename = $activityEntity->getMedia()?->getFilename()
                ?? throw new EntityNotFoundException('Activity does not have a media');

            $path = $this->activityMediaService->buildActivityMediaPath($filename);

            return $this->file($path, disposition: ResponseHeaderBag::DISPOSITION_INLINE);

        } catch (EntityNotFoundException $e) {
            return $this->buildResponse(Response::HTTP_NOT_FOUND, $e->getMessage());

        } catch (Throwable $e) {
            return $this->buildResponse(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

}
