<?php

namespace App\Controller;

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
        // converti le contenu de valeur type JSON en valeur PHP
        $customerDatas = json_decode($request->getContent(), true);
        // injecte SERVICE createCustomer 
        // passer le contenu de la requête en PROP
        // retourne un objet de valeur php 
        $customer = $this->customerService->createCustomer($customerDatas);
        // converti l'objet PHP en valeur JSON
        $serializeCustomer = $this->serializer->serialize($customer, 'json');
        // retourne le json 
        // true indique que la donnée est en JSON
        return new JsonResponse($serializeCustomer, Response::HTTP_OK, [], true);
    }

    #[Route('/api/customers/create-table', name: 'create_table_customer', methods: ['POST'])]
    public function createTableCustomer() : JsonResponse
    {
        // créé la collection Customer si elle n'existe pas déjà
        return new JsonResponse($this->customerService->createCollection());
    }

    #[Route('/api/customers/{customerId}', name:'update_customer', methods: ['PUT'])]
    public function updateCustomer($customerId, Request $request) : JsonResponse {
        // récupère le contenu de la requête
        // converti le contenu de la requête de sa valeur type json en valeur type PHP
        $requestDatas = json_decode($request->getContent(), true);
        // injecte service updateCustomer 
        // service retourne un objet PHP customer
        $customer = $this->customerService->updateCustomer($customerId, $requestDatas);
        // converti l'objet php au format JSON
        $serializeCustomer = $this->serializer->serialize($customer, 'json');
        // retourne au client une réponse au format JSON
        // true indique que la donnée est au format JSON
        return new JsonResponse($serializeCustomer, Response::HTTP_OK, [], true);
    }

    #[Route('/api/customers/{customerId}', name: 'delete_customer', methods: ['DELETE'])]
    public function deleteCustomer($customerId) 
    {
        // injecte SERVICE deleteCustomer
        // retourne au format JSON la réponse du service
        $response = $this->customerService->deleteCustomer($customerId);
        return new JsonResponse($response);
    }

    #[Route('/api/customers/{firstName}-{lastName}', name: 'get_customer', methods: ['GET'])]
    public function getCustomer($firstName, $lastName) : JsonResponse
    // obtiens les valeurs passées en param dynamiques à l'url
    {
        // injecte le SERVICE getCustomer
        // service prend les param dynamique en PROP
        // serivce retourne un objet PHP
        $customer = $this->customerService->getCustomer($firstName, $lastName);
        // converti l'objet PHP en JSON
        $serializeResponse = $this->serializer->serialize($customer, 'json');
        // retourne le JSON dans un nouvel objet json response 
        // true indique que la donnée est du JSON
        return new JsonResponse($serializeResponse, 200, [], true);
    }

    #[Route('/api/customers/{customerId}/contracts', name: "get_contract_from_customerId", methods: ['GET'])]
    public function getContractFromCustomerId($customerId): JsonResponse
    // injecte SERVICE getContractFromCustomerId 
    // service prend en 1 valeur en prop
    // cette valeur est passée en prop à la méthode
    // cette valeur est récupérée dynamiquement depuis l'url
    {
        $contractList = $this->customerService->getContractFromCustomerId($customerId);
        // le service retourne une réponse de valeur type php
        // on converti la réponse du service dans un format manipulable par le client
        // on converti la réponse au format JSON
        $serializeList = $this->serializer->serialize($contractList, 'json', [
            'groups' => ['contract', 'billing']
        ]);
        // on retourne la donnée au format JSON dans une nouvelle reponse JSON
        // la prop TRUE indique que la donnée est au format JSON
        return new JsonResponse($serializeList, Response::HTTP_OK, [], true);

    }

    #[Route('/api/customers/{customerId}/contracts/current', name: "get_current_contracts_from_customer_id", methods: ['GET'])]
    public function getCurrentContractsFromCustomer($customerId, Request $request) : JsonResponse
    // injecte le SERVICE getContractFromCustomer
    // service prend 1 valeur en PROP
    // on passe cette valeur en tant que prop à la méthode
    {
        // le service 1 retourne la liste de TOUS LES CONTRATS dont l'idCustomer correspond à sa prop
        $contracts = $this->customerService->getContractFromCustomerId($customerId);
        // le service 2 retourne la liste des contrats EN COURS
        // contrats en cours à partir du tableau obtenu via le service 1 
        // retourne un objet PHP qui doit être converti au format JSON pour $etre manipulé par le client
        $currentContracts = $this->customerService->getCurrentContractsFromCustomer($contracts);
        // converti l'objet au format JSON
        // traite le probleme des references circulaires à l'aide de la prop group
        $serializeContracts = $this->serializer->serialize($currentContracts, 'json', ['groups' => ['contract', 'billing']]);
        // retourne une nouvelle réponse JSON 
        // TRUE indique que la donnée transmise ast au format JSON
        return new JsonResponse($serializeContracts, Response::HTTP_OK, [], true);
    }


    #[Route('/api/customers/contracts/late/on-average', name: "get_late_contract_average_rate_per_customer", methods: ['GET'])]
    public function getLateContractOnAverage(ContractService $contractService) : JsonResponse 
    { 
        // injecte le SERVICE getLateContractsOnAverageByCustomer 
        // service prend 1 valeur en prop
        // cette valeur est une instance de l'objet contractService
        // ce service retourne une tableau d'objet PHP
        $lateContracts = $this->customerService->getLateContractsOnAverageByCustomer($contractService);
        // converti le tableau d'objet PHP dansu n format JSON
        // le probleme des ref circulaires traité avec le prop group
        $serializeContract = $this->serializer->serialize($lateContracts, 'json', [
            'groups' => ['contract', 'billing']
        ]);
        // retourne un objet JSON en tant que valeur transmise par l'objet new json response
        // true indique que la valeur est au format JSON
        return new JsonResponse($serializeContract, Response::HTTP_OK, [], true);
    }
    
    #[Route('/api/customers/contracts', name: 'get_contract_groupby_customer', methods: ['GET'])]
    public function getContractsGroupByCustomer()
    {
        // injecte SERVICE qui retourne un objet PHP
        $contracts = $this->customerService->getContractsGroupByCustomer();
        // convertir l'objet PHP en JSON
        $serializeContracts = $this->serializer->serialize($contracts, 'json', ['groups' => ['contract', 'billing']]);
        // retourner le JSON dans une nouvelle réponse json
        // true indique que la donnée est déjà au format JSON
        return new JsonResponse($serializeContracts, Response::HTTP_OK, [], true);
    }




}
