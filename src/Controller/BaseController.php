<?php

namespace App\Controller;

use App\Model\DataObject;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class BaseController extends AbstractController
{

    /**
     * Parses the JSON body from the given request and returns it wrapped in a {@link DataObject}.
     *
     * @param Request $request
     * @return DataObject
     * @throws InvalidRequestBodyException if the request's body is not a valid JSON string
     */
    protected function getRequestBody(Request $request): DataObject
    {
        $data = json_decode($request->getContent())
            ?? throw new InvalidRequestBodyException();

        return new DataObject($data);
    }

    /**
     * Build a JSON response with the given data.
     *
     * @param int $statusCode
     * @param string $message
     * @param array $data
     * @return JsonResponse
     */
    protected function buildResponse(int $statusCode, string $message = '', array $data = []): JsonResponse
    {
        $responseBody = [
            'datetime' => (new DateTime())->format(DATE_ATOM),
            'message' => $message,
            'data' => $data,
        ];

        return $this->json($responseBody, $statusCode);
    }
}
