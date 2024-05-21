<?php

// src/Controller/SomeController.php
namespace App\Controller;

use App\Service\Security\ApiTokenService;
use Symfony\Component\HttpFoundation\Cookie;
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
        $this->apiTokenService = $apiTokenService;
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request)
    {
        $requestDatas = json_decode($request->getContent(), true);
        $username = $requestDatas['username'];
        $password = $requestDatas['password'];
        $token = $this->apiTokenService->getToken($username, $password);

        if ($token) {
            // Create a new Response object to set the cookie
            $response = new Response('Login successful');
            $response->headers->setCookie(new Cookie(
                'token',
                $token,
                time() + (60 * 60),
                '/',
                null,
                false,
                true
            ));
            return new JsonResponse($token, Response::HTTP_OK, []);
        }

        return new Response('Login failed', Response::HTTP_UNAUTHORIZED);
    }

}
