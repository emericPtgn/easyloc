<?php

namespace App\Controller;


use App\Service\Billing\BillingService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class BillingController extends AbstractController
{

    private $billingService;

    public function __construct(BillingService $billingService){
        $this->billingService = $billingService;
    }

    #[Route('api/billing', name: 'create_billing', methods: ['POST'])]
    public function createBilling(Request $request)
    {
        if($request->query->get('action') === 'create-table'){
            return $this->billingService->createTable($request);
        }
        return $this->billingService->createBilling($request);
    }

    #[Route('/api/billing', name: 'update_billing', methods: ['PUT'])]
    public function updateBilling(Request $request) : JsonResponse
    {
        return $this->billingService->updateBilling($request);
    }

    #[Route('/api/billing', name: 'delete_billing', methods: ['DELETE'])]
    public function deleteBilling(Request $request) {
        return $this->billingService->deleteBilling($request);
    }

    #[Route('/api/billing', name: 'get_billing', methods: ['GET'])]
    public function getBilling(Request $request)
    {
        return $this->billingService->getBilling($request);
    }
}
