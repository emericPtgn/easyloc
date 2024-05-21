<?php

// src/Controller/SomeController.php
namespace App\Controller;

use App\Service\Security\ApiTokenService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SecurityController extends AbstractController
{
    private $apiTokenService;

    public function __construct(ApiTokenService $apiTokenService)
    {
        // initialise le constructeur avec la dépendance apitokenservice
        $this->apiTokenService = $apiTokenService;
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request)
    // à partir du contenu de la requête décodé (Json => Php) 
    // obtenir la valeur de username
    // obtenir la valeur de password
    {
        $requestDatas = json_decode($request->getContent(), true);
        $username = $requestDatas['username'];
        $password = $requestDatas['password'];
        // passer username et password en PROP à gettoken
        $token = $this->apiTokenService->getToken($username, $password);
        // si token obtenu, retourner le token en réponse json
        if ($token) {
            return new JsonResponse($token, Response::HTTP_OK, []);
        }
        return new Response('Login failed', Response::HTTP_UNAUTHORIZED);
    }

}
