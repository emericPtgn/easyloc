<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionListener {
    public function __invoke(ExceptionEvent $event) : void {
        $exception = $event->getThrowable();
        $message = sprintf(
            'My error says : %s with code : %s',
            $exception->getMessage(),
            $exception->getCode()
        );
    // personnaliser l'objet reponse pour afficher les détails de l'exception

    $response = new Response();
    $response->setContent($message);

    // Here is HttpExceptionInterface : certain type of exception that hold statut code and header details

    if ($exception instanceof HttpExceptionInterface){
        $response->setStatusCode($exception->getStatusCode());
        $response->headers->replace($exception->getHeaders());
    } else {
        $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    $event->setResponse($response);

    }
}