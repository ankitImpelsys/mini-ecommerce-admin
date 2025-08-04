<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class AuthCheckController extends AbstractController
{
    #[Route('/check-auth', name: 'app_check_auth', methods: ['GET'])]
    public function checkAuth(): JsonResponse
    {
        // This route is used by JavaScript to check if user is still authenticated
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['authenticated' => false], 401);
        }

        return new JsonResponse(['authenticated' => true, 'user' => $user->getUserIdentifier()]);
    }
}
