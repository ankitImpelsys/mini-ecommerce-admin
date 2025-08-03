<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class CustomLogoutHandler implements LogoutHandlerInterface
{
    public function logout(Request $request, Response $response, TokenInterface $token): void
    {
        // Clear the session completely
        $session = $request->getSession();
        $session->invalidate();

        // Set additional cache control headers
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, private, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        // Clear browser data
        $response->headers->set('Clear-Site-Data', '"cache", "storage", "executionContexts"');

        // Add a custom header to indicate logout
        $response->headers->set('X-Logout-Success', 'true');
    }
}
