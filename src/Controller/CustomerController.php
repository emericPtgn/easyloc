<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use App\Service\Customer\CustomerService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CustomerController extends AbstractController
{

    private $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    #[Route('/api/customers', name: 'create_customer', methods: ['POST'])]
    public function createCustomer(Request $request) {
        if($request->query->get('action') === 'create-collection'){
            return $this->customerService->createCollection($request);
        }
        // récupère le contenu de la requête
        $requestData = json_decode($request->getContent(), true);
        // validation
        $customer = $this->customerService->createCustomer($requestData);
        // renvoi 
        return $this->json(['customer' => $customer]);
    }

    #[Route('/api/customers', name:'update_customer', methods: ['PUT'])]
    public function updateCustomer(Request $request) : JsonResponse {
        // injecter méthode du service customerService et récupérer sa réponse
        return $this->customerService->updateCustomer($request);
    }

    #[Route('/api/customers', name: 'delete_customer', methods: ['DELETE'])]
    public function deleteCustomer(Request $request) {
        return $this->customerService->deleteCustomer($request);
    }

    #[Route('/api/customers/{firstName}/{lastName}', name: 'get_customer', methods: ['GET'])]
    public function getCustomer(Request $request, LoggerInterface $logger) : JsonResponse
    {
        // Appeler le service approprié pour traiter la demande
        $serializeCustomer = $this->customerService->getCustomer($request, $logger);
        // Retourner une réponse JSON
        return new JsonResponse($serializeCustomer, 200, [], true);
    }

    #[Route('/api/customers', name: 'get_customer_detail', methods: ['GET'])]
    public function getCustomerDetails(Request $request) {
        return $this->customerService->getCustomer($request);
    }

}
