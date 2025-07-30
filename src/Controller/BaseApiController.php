<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

abstract class BaseApiController extends AbstractController
{
    protected function isOwnedByCurrentUser($entity): bool
    {
        return method_exists($entity, 'getUser') && $entity->getUser() === $this->getUser();
    }

    protected function unauthorizedResponse(): JsonResponse
    {
        return $this->json(['error' => 'Unauthorized or not found'], 403);
    }
}
