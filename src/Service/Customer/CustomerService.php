<?php

namespace App\Service\Customer;
use App\Document\Customer;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;

class CustomerService {
    private $dm;
    private $serializer;

    public function __construct(DocumentManager $dm, SerializerInterface $serializer){
        $this->dm = $dm;
        $this->serializer = $serializer;
    }

    public function getCustomerList() : JsonResponse {
        $customers = $this->dm->getRepository(Customer::class)->findAll();
        // Sérialise les objets Customer en utilisant le serializer
        $serializedCustomers = $this->serializer->serialize($customers, 'json');
        // Retourne la réponse JSON
        return new JsonResponse($serializedCustomers, Response::HTTP_OK, [], true);
    }

    public function createCustomer(array $customerData): Customer {
        // nouvel objet client
        $customer = new Customer();
        // met à jour les infos clients à l'aide du tableau associatif fourni dans le controlleur 
        $customer->setFirstName($customerData['firstName']);
        $customer->setLastName($customerData['lastName']);
        $customer->setAdress($customerData['adress']);
        $customer->setPermitNumber($customerData['permitNumber']);
        // enregistre le client en base de donnée
        $this->dm->persist($customer);
        $this->dm->flush();
        // retourne la réponse JSON 
        return $customer;
    }
}
