<?php

namespace App\Controller;

use App\Service\Customer\CustomerService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CustomerController extends AbstractController
{

    private $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    #[Route('/api/customers', name: 'list_customer', methods: ['GET'])]
    public function getCustomerList(CustomerService $customerService, SerializerInterface $serializer): JsonResponse
    {
        // Appel de la méthode getCustomerList() du service
        $customers = $customerService->getCustomerList();
        // Retourne la liste client sous forme de chaîne de caractère
        return new JsonResponse($customers, Response::HTTP_OK, [], true);
    }

    #[Route('/api/customers/', name: 'create_customer', methods: ['POST'])]
    public function createCustomer(Request $request) : JsonResponse {
        // récupère le contenu de la requête
        $requestData = json_decode($request->getContent(), true);
        // validation
        $customer = $this->customerService->createCustomer($requestData);
        // renvoi 
        return $this->json(['customer' => $customer]);
    }

}
