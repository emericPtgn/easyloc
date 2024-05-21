<?php

// src/Service/Security/ApiTokenService.php
namespace App\Service\Security;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;

class ApiTokenService
{
    private $forward;
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
        $this->forward = HttpClient::createForBaseUri('http://localhost', [
            'verify_peer' => false,
            'verify_host' => false,
            'cafile' => __DIR__.'/Users/emericp/.symfony5/certs',
        ]);
    }

    public function getToken(): ?string
    {
        try {
            $response = $this->forward->request(
                'POST',
                '/api/login_check',
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'username' => 'petitgenet.emeric@gmail.com',
                        'password' => 'MDP1995',
                    ],
                ]
            );

            $data = $response->toArray();
            $token = $data['token'] ?? null;

            if ($token) {
                $cookie = new Cookie(
                    'token',
                    $token,
                    time() + (60 * 60), // Expires in 1 hour
                    '/', // Path
                    null, // Domain
                    false, // Secure
                    true // HttpOnly
                );

                // Create a new Response object to set the cookie
                $response = new Response();
                $response->headers->setCookie($cookie);

                return $token;
            }

            return null;
        } catch (TransportExceptionInterface $e) {
            // Handle API connection errors
            dd($e->getMessage());
        }
    }


}
