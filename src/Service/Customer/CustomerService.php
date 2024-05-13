<?php

namespace App\Service\Customer;
use App\Document\Customer;
use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
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

    public function updateCustomer(string $id, array $customerData): JsonResponse {
        $customer = $this->dm->getRepository(Customer::class)->find($id);
    
        if (!$customer) {
            throw new \InvalidArgumentException('Customer not found for ID ' . $id);
        }
    
        // Mise à jour des données du client si elles sont fournies
        if (isset($customerData['firstName'])) {
            $customer->setFirstName($customerData['firstName']);
        }
        if (isset($customerData['lastName'])) {
            $customer->setLastName($customerData['lastName']);
        }
        if (isset($customerData['adress'])) {
            $customer->setAdress($customerData['adress']);
        }
        if (isset($customerData['permitNumber'])) {
            $customer->setPermitNumber($customerData['permitNumber']);
        }
        $this->dm->flush();

        $serializeCustomer = $this->serializer->serialize($customer, 'json');
    
        // Retourner les données du client sous forme de tableau associatif pour une meilleure manipulation
        return new JsonResponse($serializeCustomer, 200, [], true);
    }

    public function deleteCustomer(string $id) : Response {
        $customer = $this->dm->getRepository(Customer::class)->find($id);
        if (!$customer) {
            throw new \InvalidArgumentException('Customer not found for ID ' . $id);
        }
        $customer = $this->dm->getRepository(Customer::class)->find($id);
        $this->dm->remove($customer);
        $this->dm->flush();
        return new Response ('operation successfull : customer deleted');
    }

    // recherche client à partir du nom / prénom passé en paramètres au controlleur
    public function getCustomer(Request $request, LoggerInterface $logger) : JsonResponse {

        $firstName = $request->attributes->get('firstName');
        $lastName = $request->attributes->get('lastName');
        $customer = $this->dm->getRepository(Customer::class)->findOneBy(['firstName' => $firstName, 'lastName' => $lastName]);

        // Utiliser les paramètres d'URL 
        $logger->info('First Name: ' . $firstName);
        $logger->info('Last Name: ' . $lastName);
        $serializeCustomer = $this->serializer->serialize($customer, 'json');

        return new JsonResponse($serializeCustomer, 200, [], true);
        }
        
    public function getCustomerDetails(Request $request, LoggerInterface $logger) : JsonResponse {
        $firstName = $request->query->get('firstName');
        $lastName = $request->query->get('lastName');
        $customer = $this->dm->getRepository(Customer::class)->findOneBy(['firstName' => $firstName, 'lastName' => $lastName]);
        $serializeCustomer = $this->serializer->serialize($customer, 'json');
        return new JsonResponse($serializeCustomer, 200, [], true);
    }
}
