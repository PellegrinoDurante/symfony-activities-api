<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Service\ObjectSerializer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Throwable;

class CategoryController extends BaseController
{
    public function __construct(
        private CategoryRepository     $categoryRepository,
        private EntityManagerInterface $entityManager,
        private ObjectSerializer       $serializer,
    )
    {
    }

    /**
     * @Route("/categories", methods={"GET"}, name="categories_list")
     */
    public function listCategories(): JsonResponse
    {
        try {
            // Get all categories
            $categoryEntities = $this->categoryRepository->findAll();

            // Build and return response
            $categories = $this->serializer->normalize($categoryEntities, null, [
                AbstractNormalizer::GROUPS => 'category',
                AbstractNormalizer::IGNORED_ATTRIBUTES => ['activities'],
            ]);
            $message = sprintf('Found %d categories', count($categories));
            return $this->buildResponse(Response::HTTP_OK, $message, $categories);

        } catch (Throwable $e) {
            return $this->buildResponse(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    /**
     * @Route("/categories/{id}", methods={"GET"}, name="categories_get", requirements={"id"="\d+"})
     */
    public function getCategory(int $id): JsonResponse
    {
        try {
            // Search the category by ID; throws an exception if not found
            $categoryEntity = $this->categoryRepository->find($id)
                ?? throw new EntityNotFoundException();

            // Category found
            $category = $this->serializer->normalize($categoryEntity, null, [AbstractNormalizer::IGNORED_ATTRIBUTES => ['activities']]);
            return $this->buildResponse(Response::HTTP_OK, 'Category found', $category);

        } catch (EntityNotFoundException) {
            return $this->buildResponse(Response::HTTP_NOT_FOUND, 'Category not found');

        } catch (Throwable $e) {
            return $this->buildResponse(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    /**
     * @Route("/categories", methods={"POST"}, name="categories_create")
     */
    public function createCategory(Request $request): JsonResponse
    {
        try {
            // Get request data
            $data = $this->getRequestBody($request);

            // Build a new category
            $category = new Category();
            $category->setName($data->getRequired('name'));

            // Persist the category
            $this->entityManager->persist($category);
            $this->entityManager->flush();

            // Build and return response
            return $this->buildResponse(Response::HTTP_OK, 'Category created', ['id' => $category->getId()]);

        } catch (InvalidRequestBodyException | MissingRequiredFieldException $e) {
            return $this->buildResponse(Response::HTTP_BAD_REQUEST, $e->getMessage());

        } catch (Throwable $e) {
            return $this->buildResponse(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    /**
     * @Route("/categories/{id}", methods={"PUT"}, name="categories_edit", requirements={"id"="\d+"})
     */
    public function editCategory(int $id, Request $request): JsonResponse
    {
        try {
            // Get request data
            $data = $this->getRequestBody($request);

            // Search the category by ID; throws an exception if not found
            $category = $this->categoryRepository->find($id)
                ?? throw new EntityNotFoundException();

            // Update the category
            $category->setName($data->getRequired('name'));
            $this->entityManager->flush();

            // Build and return response
            return $this->buildResponse(Response::HTTP_OK, 'Category edited');

        } catch (InvalidRequestBodyException | MissingRequiredFieldException $e) {
            return $this->buildResponse(Response::HTTP_BAD_REQUEST, $e->getMessage());

        } catch (EntityNotFoundException) {
            return $this->buildResponse(Response::HTTP_NOT_FOUND, 'Category not found');

        } catch (Throwable $e) {
            return $this->buildResponse(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    /**
     * @Route("/categories/{id}", methods={"DELETE"}, name="categories_delete", requirements={"id"="\d+"})
     */
    public function deleteCategory(int $id): JsonResponse
    {
        try {
            // Search the category by ID; throws an exception if not found
            $category = $this->categoryRepository->find($id)
                ?? throw new EntityNotFoundException();

            // Delete the category
            $this->entityManager->remove($category);
            $this->entityManager->flush();

            // Build and return response
            return $this->buildResponse(Response::HTTP_OK, 'Category deleted');

        } catch (EntityNotFoundException) {
            return $this->buildResponse(Response::HTTP_NOT_FOUND, 'Category not found');

        } catch (Throwable $e) {
            return $this->buildResponse(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }
}
