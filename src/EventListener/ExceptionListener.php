<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Customize response
        $response = new JsonResponse([
            'error' => 'An error occurred.',
            'details' => $exception instanceof HttpExceptionInterface ? $exception->getMessage() : 'Internal server error.',
        ]);

        $statusCode = $exception instanceof HttpExceptionInterface
            ? $exception->getStatusCode()
            : JsonResponse::HTTP_INTERNAL_SERVER_ERROR;

        $response->setStatusCode($statusCode);
        $event->setResponse($response);
    }
}
