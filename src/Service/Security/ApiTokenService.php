<?php

// src/Service/ApiTokenService.php
namespace App\Service\Security;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class ApiTokenService
{
    private $forward;

    public function __construct()
    {
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
            return $data['token'] ?? null;
        } catch (TransportExceptionInterface $e) {
            // Handle API connection errors
            dd($e->getMessage());
        }
    }
}
