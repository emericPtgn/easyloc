<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use App\Service\Contract\ContractService;
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
    private $serializer;

    public function __construct(CustomerService $customerService, SerializerInterface $serializer)
    {
        $this->customerService = $customerService;
        $this->serializer = $serializer;
    }

    #[Route('/api/customers', name: 'create_customer', methods: ['POST'])]
    public function createCustomer(Request $request) : JsonResponse 
    {
        // récupère le contenu de la requête
        $customerDatas = json_decode($request->getContent(), true);
        // validation
        $customer = $this->customerService->createCustomer($customerDatas);
        // renvoi du json
        $serializeCustomer = $this->serializer->serialize($customer, 'json');
        return new JsonResponse($serializeCustomer, Response::HTTP_OK, [], true);
    }

    #[Route('/api/customers/create-table', name: 'create_table_customer', methods: ['POST'])]
    public function createTableCustomer() : JsonResponse
    {
        return new JsonResponse($this->customerService->createCollection());
    }

    #[Route('/api/customers/{customerId}', name:'update_customer', methods: ['PUT'])]
    public function updateCustomer($customerId, Request $request) : JsonResponse {
        // injecter méthode du service customerService et récupérer sa réponse
        $requestDatas = json_decode($request->getContent(), true);
        $customer = $this->customerService->updateCustomer($customerId, $requestDatas);
        $serializeCustomer = $this->serializer->serialize($customer, 'json');
        return new JsonResponse($serializeCustomer, Response::HTTP_OK, [], true);
    }

    #[Route('/api/customers/{customerId}', name: 'delete_customer', methods: ['DELETE'])]
    public function deleteCustomer($customerId) 
    {
        $response = $this->customerService->deleteCustomer($customerId);
        return new JsonResponse($response);
    }

    #[Route('/api/customers/{firstName}-{lastName}', name: 'get_customer', methods: ['GET'])]
    public function getCustomer($firstName, $lastName) : JsonResponse
    {
        $customer = $this->customerService->getCustomer($firstName, $lastName);
        $serializeResponse = $this->serializer->serialize($customer, 'json');
        return new JsonResponse($serializeResponse, 200, [], true);
    }

    #[Route('/api/customers/{customerId}/contracts', name: "get_contract_from_customerId", methods: ['GET'])]
    public function getContractFromCustomerId($customerId): JsonResponse
    {
        $contractList = $this->customerService->getContractFromCustomerId($customerId);
        $serializeList = $this->serializer->serialize($contractList, 'json', [
            'groups' => ['contract', 'billing']
        ]);
        return new JsonResponse($serializeList, Response::HTTP_OK, [], true);

    }

    #[Route('/api/customers/{customerId}/contracts/current', name: "get_current_contracts_from_customer_id", methods: ['GET'])]
    public function getCurrentContractsFromCustomer($customerId, Request $request) : JsonResponse
    {
        $contracts = $this->customerService->getContractFromCustomerId($customerId);
        $currentContracts = $this->customerService->getCurrentContractsFromCustomer($contracts);
        $serializeContracts = $this->serializer->serialize($currentContracts, 'json', ['groups' => ['contract', 'billing']]);
        return new JsonResponse($serializeContracts, Response::HTTP_OK, [], true);
    }


    #[Route('/api/customers/contracts/late/on-average', name: "get_late_contract_average_rate_per_customer", methods: ['GET'])]
    public function getLateContractOnAverage(ContractService $contractService) : JsonResponse 
    { 
        $lateContracts = $this->customerService->getLateContractsOnAverageByCustomer($contractService);
        $serializeContract = $this->serializer->serialize($lateContracts, 'json', [
            'groups' => ['contract', 'billing']
        ]);
        return new JsonResponse($serializeContract, Response::HTTP_OK, [], true);
    }
    



}
