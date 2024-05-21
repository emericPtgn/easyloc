<?php

// src/Service/CustomHttpClient.php
namespace App\Service\Security;

use App\Service\Security\ApiTokenService; // Ajoutez cette ligne
use Symfony\Component\HttpClient\HttpClient;

class CustomHttpClient
{
    private $httpClient;
    private $apiTokenService;

    public function __construct(ApiTokenService $apiTokenService) // Modifiez cette ligne
    {
        $this->httpClient = HttpClient::create();
        $this->apiTokenService = $apiTokenService;
    }

    public function request(string $method, string $url, array $options = [])
    {
        $token = $this->apiTokenService->getToken();

        if ($token) {
            $options['headers']['Authorization'] = 'Bearer ' . $token;
        }

        $response = $this->httpClient->request($method, $url, $options);

        return $response;
    }

}
