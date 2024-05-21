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
        // billingService réuni les services associés au controlleur Billing
        $this->billingService = $billingService;
        $this->serializer = $serializer;
    }

    #[Route('/api/billing/{idContrat}', name: 'create_billing', methods: ['POST'])]
    public function createBilling($idContrat, Request $request)
    {
        // récupère dans le BODY de la requête la valeur de AMOUNT => passe en PROP au SERVICE createBilling
        $requestDatas = json_decode($request->getContent(), true);
        $montant = $requestDatas['amount'];
        $billing = $this->billingService->createBilling($idContrat, $montant);
        
        // converti l'objet PHP billing en JSON
        $serializeBilling = $this->serializer->serialize($billing, 'json', [
            'groups' => ['billing'],
        ]);        
        // renvoi une réponse JSON 
        return new JsonResponse($serializeBilling, Response::HTTP_OK, [], true);
    }

    #[Route('/api/billing/create-table', name: 'create_table_billing', methods: ['POST'])]
    // injecte SERVICE createTable pour CREER table billing SI PAS existante
    public function createTable () : Response
    {
        return new Response($this->billingService->createTable());
    }

    #[Route('/api/billing/{billingId}', name: 'update_billing', methods: ['PUT'])]
    // injecte SERVICE updateBilling avec 2 PROP : billingId(str) + montant(int) => renvoi objet PHP => converti en JSON // group pour éviter référence circulaire entre entités billing et contract
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
    // injecte SERVICE deleteBilling - utilise param dyn de l'url => passe le param en PROP au service, retourne le service
    public function deleteBilling($billingId) {
        return $this->billingService->deleteBilling($billingId);
    }

    #[Route('/api/billing/{billingId}', name: 'get_billing', methods: ['GET'])]
    // injecte SERVICE getBilling qui prend 1 PROP billingId(str) => renvoi obj PHP => converti en JSON avec mention du group pour éviter référence circulaire entre entités billing et contract
    // 
    public function getBilling($billingId)
    {   
        $billing = $this->billingService->getBilling($billingId);
        $serializedBilling = $this->serializer->serialize($billing, 'json', [
            'groups' => ['billing']
        ]);
        return new JsonResponse($serializedBilling, Response::HTTP_OK, [], true);
    }
}
