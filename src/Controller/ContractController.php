<?php

namespace App\Controller;

use App\Service\Contract\ContractService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ContractController extends AbstractController
{
    private $contractService;
    private $logger;

    public function __construct(ContractService $contractService, LoggerInterface $logger){
        $this->contractService = $contractService;
        $this->logger = $logger;
    }

    #[Route('/api/contract', name: 'create_contract', methods: ['POST'])]
    public function createContract(Request $request)
    {
        $this->logger->info('TEST CREATE CONTRACT ACTIF');
        if($request->query->get('action') === 'create-table'){
            return $this->contractService->createTable($request);
        }
        return $this->contractService->createContract($request);
    }

    #[Route('/api/contract', name: 'update_contract', methods: ['PUT'])]
    public function updateContract(Request $request, LoggerInterface $logger): JsonResponse
    {
        $response = $this->contractService->updateContract($request, $logger);
        return new JsonResponse($response, 200, [], true);
    }

    #[Route('/api/contract', name: 'delete_contract', methods: ['DELETE'])]
    public function deleteContract(Request $request) : Response {
        $response = $this->contractService->deleteContract($request);
        return new Response ($response, Response::HTTP_OK);
    }

    #[Route('/api/contract', name: "get_contracts", methods: ['GET'])]
    public function getContracts(Request $request) {
        return $this->contractService->getContracts($request); 
    }
}
