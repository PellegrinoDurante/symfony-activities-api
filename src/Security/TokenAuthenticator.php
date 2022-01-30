<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use DateTime;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class TokenAuthenticator extends AbstractGuardAuthenticator
{

    public function __construct(private UserRepository $userRepository)
    {
    }

    public function supports(Request $request): bool
    {
        // Check if bearer token exists in Authorization header
        return $request->headers->has("Authorization")
            && str_starts_with($request->headers->get('Authorization'), 'Bearer ');
    }

    public function getCredentials(Request $request)
    {
        // Get bearer token
        $authHeader = $request->headers->get('Authorization');
        return substr($authHeader, 7);
    }

    public function getUser($credentials, UserProviderInterface $userProvider): User|null
    {
        return $this->userRepository->findOneByToken($credentials);
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        // DEV-NOTE: per semplicità i token non hanno una scadenza. Se l'utente con il token è stato trovato allora
        // è autorizzato
        return true;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        return $this->buildNotAuthorizedResponse();
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return $this->buildNotAuthorizedResponse();
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }

    private function buildNotAuthorizedResponse(string $message = 'Invalid credentials'): JsonResponse
    {
        return new JsonResponse([
            'datetime' => (new DateTime())->format(DATE_ATOM),
            'message' => $message,
        ], 401);
    }
}
