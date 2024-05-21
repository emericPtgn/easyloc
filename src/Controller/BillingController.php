<?php

namespace App\Controller;


use App\Service\Billing\BillingService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class BillingController extends AbstractController
{

    private $billingService;
    private $serializer;

    public function __construct(BillingService $billingService, SerializerInterface $serializer){
        $this->billingService = $billingService;
        $this->serializer = $serializer;
    }

    #[Route('/api/billing/{idContrat}', name: 'create_billing', methods: ['POST'])]
    public function createBilling($idContrat, Request $request)
    {

        $requestDatas = json_decode($request->getContent(), true);
        $montant = $requestDatas['amount'];
        $billing = $this->billingService->createBilling($idContrat, $montant);
        
        $serializeBilling = $this->serializer->serialize($billing, 'json', [
            'groups' => ['billing'],
        ]);        
        
        return new JsonResponse($serializeBilling, Response::HTTP_OK, [], true);
    }

    #[Route('/api/billing/create-table', name: 'create_table_billing', methods: ['POST'])]
    public function createTable () : Response
    {
        return new Response($this->billingService->createTable());
    }

    #[Route('/api/billing/{billingId}', name: 'update_billing', methods: ['PUT'])]
    public function updateBilling($billingId, Request $request) : JsonResponse
    {
        $billingDatas = json_decode($request->getContent(), true);
        $response = $this->billingService->updateBilling($billingId, $billingDatas['amount']);
        $jsonResponse = $this->serializer->serialize($response, 'json', [
            'groups' => ['billing']
        ]);
        return new JsonResponse($jsonResponse, Response::HTTP_OK, [], true);
    }

    #[Route('/api/billing/{billingId}', name: 'delete_billing', methods: ['DELETE'])]
    public function deleteBilling($billingId) {
        return $this->billingService->deleteBilling($billingId);
    }

    #[Route('/api/billing/{billingId}', name: 'get_billing', methods: ['GET'])]
    public function getBilling($billingId)
    {   
        $billing = $this->billingService->getBilling($billingId);
        $serializedBilling = $this->serializer->serialize($billing, 'json', [
            'groups' => ['billing']
        ]);
        return new JsonResponse($serializedBilling, Response::HTTP_OK, [], true);
    }
}
