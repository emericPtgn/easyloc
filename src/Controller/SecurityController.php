<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use App\Service\Security\ApiTokenService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(Request $request, ApiTokenService $apiTokenService): JsonResponse
    {

        $token = $apiTokenService->getToken();

        if ($token) {
            return $this->json(['token' => $token]);
        } else {
            return $this->json(['error' => 'Invalid credentials'], 401);
        }
    }


    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
