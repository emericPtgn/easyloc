<?php

namespace App\Controller;

use App\Repository\ContractRepository;
use App\Service\Contract\ContractService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ContractController extends AbstractController
{
    private $contractService;
    private $logger;
    private $contractRepo;
    private $serializer;

    public function __construct(ContractService $contractService, LoggerInterface $logger, 
    ContractRepository $contractRepo, SerializerInterface $serializer){
        // initialise controlleur avec interface de service, repository associé, et serializer outil de conversion deconversion au json
        $this->contractService = $contractService;
        $this->logger = $logger;
        $this->contractRepo = $contractRepo;
        $this->serializer = $serializer;
    }

    #[Route('/api/contract', name: 'create_contract', methods: ['POST'])]
    public function createContract(Request $request) : JsonResponse
    // param dynamiques + contenu de la requête passé en PROP au service createContact
    // renvoi un objet PHP converti au JSON 
    // groups permet d'éviter le problème de référence circulaire entre entités liées CONTRACT <-> BILLING 
    {
        $vehicleId = $request->query->get('vehicleId');
        $customerId = $request->query->get('customerId');
        $contractDatas = json_decode($request->getContent(), true);
        $contract = $this->contractService->createContract($vehicleId, $customerId, $contractDatas);
        $serializeContract = $this->serializer->serialize($contract, 'json', [
            'groups' => ['contract', 'billing']
        ]);
        return new JsonResponse($serializeContract, Response::HTTP_OK, [], true);
    }


    #[Route('/api/contract/create-table', name: 'create_table_contract', methods: ['POST'])]
    public function createTable() : Response
    // injecte SERVICE createTable pour créer table SI PAS existante
    // retourne la réponse du service en new response du controlleur
    {
        return new Response($this->contractService->createTable());
    }

    #[Route('/api/contract', name: 'update_contract', methods: ['PUT'])]
    public function updateContract(Request $request): JsonResponse
    // param dynamique contractId(str) + contenu de la requête(array) => injecté dans SERVICE updateContract
    // service retourne objet PHP converti en JSON 
    // groups permet d'éviter le problème de référence circulaire entre entités liées CONTRACT <-> BILLING 
    // retourne new json response, parametre bool indique que l'objet est au format JSON
    {
        $contractId = $request->query->get('contractId');
        $updateContent = json_decode($request->getContent(), true);
        $contract = $this->contractService->updateContract($contractId, $updateContent);
        $serializeContract = $this->serializer->serialize($contract, 'json', [
            'groups' => ["billing", "contract"]
        ]);
        return new JsonResponse($serializeContract, 200, [], true);
    }

    #[Route('/api/contract/{contractId}', name: 'delete_contract', methods: ['DELETE'])]
    public function deleteContract(Request $request, $contractId) : Response {
        // param dynamique contractId(str) passé en PROP au SERVICE injecté deleteContract 
        // service retourne chaîne de caractère
        // controlleur retourne la réponse du service
        $contractId = $request->query->get('contractId');
        $response = $this->contractService->deleteContract($request, $contractId);
        return new Response ($response, Response::HTTP_OK, []);
    }



    #[Route('/api/contract/{contractId}/billings', name: "get_contract_billings", methods: ['GET'])]
    public function getBillingsFromContractId(Request $request, $contractId) : JsonResponse 
    {
        // param dynamique billingId(str) passé en PROP au SERVICE injecté getBillingsFromContractId
        // service retourne un objet PHP que l'on converti en JSON 
        // groups permet d'éviter le problème de référence circulaire entre entités liées CONTRACT <-> BILLING 
        $billings = $this->contractService->getBillingsFromContractId($contractId);
        $serializeBillings = $this->serializer->serialize($billings, 'json', [
            'groups' => ['contract', 'billing']
        ]);
        return new JsonResponse($serializeBillings, Response::HTTP_OK, [], true);
    }


    #[Route('/api/contract/{contractId}/is-paid', name: "check_contract_isPaid", methods: ['GET'])]
    public function checkContractIsPaid($contractId) : JsonResponse 
        // param dynamique contractId(str) passé en PROP au service injecté checkContractIsPaid
        // service retourne un objet PHP avec 2 clés : bool si contrat est payé ; montant restant à payer
        // controlleur converti la réponse du service en JSON et retourne une nouvelle réponse JSON 
        // paramètre TRUE indique que la donnée est JSON
    {
        $isPaid = $this->contractService->checkContractIsPaid($contractId);
        $serializeResponse = $this->serializer->serialize($isPaid, 'json');
        return new JsonResponse($serializeResponse, Response::HTTP_OK, [], true);
    }


    #[Route('/api/contract/{contractId}', name: "get_contract", methods: ['GET'])]
    public function getContract($contractId) : JsonResponse 
    // param dynamique contractId(str) passé en PROP au service injecté getContract
    // service retourne une réponse type objet PHP que l'on converti en json
    // groups permet d'éviter le problème de référence circulaire entre entités liées CONTRACT <-> BILLING 
    // paramètre TRUE indique que la donnée est JSON
    {
        $contract = $this->contractService->getContract($contractId);
        $serializeContract = $this->serializer->serialize($contract, 'json', [
            'groups' => ['contract', 'billing']
        ]);
        return new JsonResponse($serializeContract, Response::HTTP_OK, [], true);
    }

    #[Route('/api/contract/unpaid', name: "get_unpaid_contracts", methods: ['GET'])]
    public function getUnpaidContracts() : JsonResponse 
    // injecte SERVICE getUnpaidContract qui retourne un objet PHP de type tableau
    // tableau converti en json et retourné en tant que réponse du controlleur
    // groups permet d'éviter le problème de référence circulaire entre entités liées CONTRACT <-> BILLING 
    // paramètre TRUE indique que la donnée est JSON        
    {
        $unpaidContracts = $this->contractService->getUnpaidContracts();
        $serializeContract = $this->serializer->serialize($unpaidContracts, 'json', [
            'groups' => ['contract', 'billing']
        ]);
        return new JsonResponse($serializeContract, Response::HTTP_OK, [], true);
    }

    #[Route('/api/contract/late', name: "get_late_contract", methods: ['GET'])]
    public function getLateContracts() : JsonResponse 
    // injecte SERVICE getLateContract 
    // retourne un objet php de type tableau
    // converti l'objet au format JSON
    // retourne nouvelle réponse JSON 
    // TRUE indique que la réponse est au format JSON
    {
        $lateContracts = $this->contractService->getLateContracts();
        $serializeContract = $this->serializer->serialize($lateContracts, 'json', [
            'groups' => ['contract', 'billing']
        ]);
        return new JsonResponse($serializeContract, Response::HTTP_OK, [], true);
    }

    #[Route('/api/contract/late/{intervalDate1}/{intervalDate2}', name: "get_late_contract", methods: ['GET'])]
    public function countLateContractBetween($intervalDate1, $intervalDate2) : JsonResponse 
    // injecte SERVICE countLateContractBetween avec 2 prop(str)
    // retourne un objet de type tableau
    // converti le tableau en JSON
    // retourne la réponse
    // true indique que la donnée est au format JSON
    { 
        $lateContracts = $this->contractService->countLateContractBetween($intervalDate1, $intervalDate2);
        $serializeContracts = $this->serializer->serialize($lateContracts, 'json', ['groups' => ['contract', 'biling']]);
        return new JsonResponse($serializeContracts, Response::HTTP_OK, [], true);
    }
}
