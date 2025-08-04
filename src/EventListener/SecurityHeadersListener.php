<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsEventListener(event: KernelEvents::RESPONSE)]
class SecurityHeadersListener
{
    public function __construct(
        private TokenStorageInterface $tokenStorage
    ) {}

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        // Only apply to main requests
        if (!$event->isMainRequest()) {
            return;
        }

        // Check if user is authenticated
        $token = $this->tokenStorage->getToken();
        $isAuthenticated = $token && $token->getUser();

        // Apply cache control headers for authenticated users or sensitive routes
        if ($isAuthenticated || $this->isSensitiveRoute($request->getPathInfo())) {
            // Prevent caching of authenticated pages
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, private, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');

            // Additional security headers
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-XSS-Protection', '1; mode=block');

            // Clear browser history on logout
            if ($request->getPathInfo() === '/logout') {
                $response->headers->set('Clear-Site-Data', '"cache", "storage"');
            }
        }
    }

    private function isSensitiveRoute(string $path): bool
    {
        $sensitiveRoutes = [
            '/order',
            '/product',
            '/category',
            '/admin',
            '/dashboard',
//            '/profile'
        ];

        foreach ($sensitiveRoutes as $route) {
            if (str_starts_with($path, $route)) {
                return true;
            }
        }

        return false;
    }
}
