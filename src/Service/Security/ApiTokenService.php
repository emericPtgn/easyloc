<?php

// src/Service/Security/ApiTokenService.php
namespace App\Service\Security;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mime\Email;
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
            // certificat auto généré dans symfony
            'cafile' => __DIR__.'/Users/emericp/.symfony5/certs',
        ]);
    }

    public function getToken(string $username, string $password)
    {
        // requête POST renvoi TOKEN au format JSON
        try {
            $response = $this->forward->request(
                'POST',
                '/api/login_check',
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'username' => $username,
                        'password' => $password,
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
