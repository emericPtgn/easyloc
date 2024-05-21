<?php

// src/Service/Security/ApiTokenService.php
namespace App\Service\Security;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
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

            return $token;
        } catch (TransportExceptionInterface $e) {
            // Handle API connection errors
            dd($e->getMessage());
        }
    }


}
