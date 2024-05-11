<?php

namespace App\Controller;

use App\Service\Customer\CustomerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class CustomerController extends AbstractController
{
    #[Route('/api/customers', name: 'customer', methods: ['GET'])]
    public function getCustomerList(CustomerService $customerService): JsonResponse
    {
        // Appel de la méthode getCustomerList() du service
        $customers = $customerService->getCustomerList();

        // Retourne la réponse JSON contenant la liste des clients
        return $customers;
    }

}
