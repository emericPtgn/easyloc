<?php

namespace App\Service\Customer;
use App\Document\Customer;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\JsonResponse;

class CustomerService {
    private $dm;

    public function __construct(DocumentManager $dm){
        $this->dm = $dm;
    }
    public function getCustomerList() : JsonResponse {
        $customers = $this->dm->getRepository(Customer::class)->findAll();
        return new JsonResponse(['customer_list' => $customers]);
    }
}