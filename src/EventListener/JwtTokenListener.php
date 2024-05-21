<?php

// src/EventListener/JwtTokenListener.php

namespace App\EventListener;

use App\Service\Security\ApiTokenService;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class JwtTokenListener
{
    private $apiTokenService;

    public function __construct(ApiTokenService $apiTokenService)
    {
        $this->apiTokenService = $apiTokenService;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        // Récupérer la requête entrante
        $request = $event->getRequest();

        // Récupérer le token JWT
        $token = $this->apiTokenService->getToken();

        // Ajouter le token JWT à l'en-tête de la requête
        if ($token) {
            $request->headers->set('Authorization', 'Bearer ' . $token);
        }
    }
}
