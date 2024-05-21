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
        $this->contractService = $contractService;
        $this->logger = $logger;
        $this->contractRepo = $contractRepo;
        $this->serializer = $serializer;
    }

    #[Route('/api/contract', name: 'create_contract', methods: ['POST'])]
    public function createContract(Request $request) : JsonResponse
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
    {
        return new Response($this->contractService->createTable());
    }

    #[Route('/api/contract', name: 'update_contract', methods: ['PUT'])]
    public function updateContract(Request $request): JsonResponse
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
        $contractId = $request->query->get('contractId');
        $response = $this->contractService->deleteContract($request, $contractId);
        return new Response ($response, Response::HTTP_OK, []);
    }



    #[Route('/api/contract/{contractId}/billings', name: "get_contract_billings", methods: ['GET'])]
    public function getBillingsFromContractId(Request $request, $contractId) : JsonResponse 
    {
        $billings = $this->contractService->getBillingsFromContractId($contractId);
        $serializeBillings = $this->serializer->serialize($billings, 'json', [
            'groups' => ['contract', 'billing']
        ]);
        return new JsonResponse($serializeBillings, Response::HTTP_OK, [], true);
    }


    #[Route('/api/contract/{contractId}/is-paid', name: "check_contract_isPaid", methods: ['GET'])]
    public function checkContractIsPaid($contractId) : JsonResponse 
    {
        $isPaid = $this->contractService->checkContractIsPaid($contractId);
        $serializeResponse = $this->serializer->serialize($isPaid, 'json');
        return new JsonResponse($serializeResponse, Response::HTTP_OK, [], true);
    }


    #[Route('/api/contract/{contractId}', name: "get_contract", methods: ['GET'])]
    public function getContract($contractId) : JsonResponse 
    {
        $contract = $this->contractService->getContract($contractId);
        $serializeContract = $this->serializer->serialize($contract, 'json', [
            'groups' => ['contract', 'billing']
        ]);
        return new JsonResponse($serializeContract, Response::HTTP_OK, [], true);
    }

    #[Route('/api/contract/unpaid', name: "get_unpaid_contracts", methods: ['GET'])]
    public function getUnpaidContracts() : JsonResponse 
    {
        $unpaidContracts = $this->contractService->getUnpaidContracts();
        $serializeContract = $this->serializer->serialize($unpaidContracts, 'json', [
            'groups' => ['contract', 'billing']
        ]);
        return new JsonResponse($serializeContract, Response::HTTP_OK, [], true);
    }

    #[Route('/api/contract/late', name: "get_late_contract", methods: ['GET'])]
    public function getLateContracts() : JsonResponse 
    {
        $lateContracts = $this->contractService->getLateContracts();
        $serializeContract = $this->serializer->serialize($lateContracts, 'json', [
            'groups' => ['contract', 'billing']
        ]);
        return new JsonResponse($serializeContract, Response::HTTP_OK, [], true);
    }

    #[Route('/api/contract/late/{intervalDate1}/{intervalDate2}', name: "get_late_contract", methods: ['GET'])]
    public function countLateContractBetween($intervalDate1, $intervalDate2) : JsonResponse 
    {
        $lateContracts = $this->contractService->countLateContractBetween($intervalDate1, $intervalDate2);
        $serializeContracts = $this->serializer->serialize($lateContracts, 'json', ['groups' => ['contract', 'biling']]);
        return new JsonResponse($serializeContracts, Response::HTTP_OK, [], true);
    }



}
