<?php

namespace App\Controller;

use App\Service\Contract\ContractService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\RedirectController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ContractController extends AbstractController
{
    private $contractService;

    public function __construct(ContractService $contractService){
        $this->contractService = $contractService;
    }

    #[Route('/api/contract', name: 'create_table', methods: ['POST'])]
    public function createTable(Request $request) 
    {
        $action = $request->query->get('action');
        if($action === 'create-table'){
            return $this->contractService->createTable($request);
        }
        return;
    }

    #[Route('/api/contract', name: 'create_contract', methods: ['POST'])]
    public function createContract(Request $request)
    {
        if($request->query->get('action') == 'create-table'){
            return $this->createTable($request);
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

    #[Route('/api/contract', name: "get_contract", methods: ['GET'])]
    public function getContract(Request $request) : JsonResponse {
        $response = $this->contractService->getContract($request);
        return new JsonResponse($response, 200, [], true);
    }
}
