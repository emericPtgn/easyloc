<?php

namespace App\Service\Customer;
use App\Document\Customer;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Collection;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface;

class CustomerService {
    private $dm;
    private $serializer;
    private $connection;

    public function __construct(DocumentManager $dm, SerializerInterface $serializer, Connection $connection){
        $this->dm = $dm;
        $this->serializer = $serializer;
        $this->connection = $connection;
    }

    public function getCustomerList() : JsonResponse {
        $customers = $this->dm->getRepository(Customer::class)->findAll();
        // Sérialise les objets Customer en utilisant le serializer
        $serializedCustomers = $this->serializer->serialize($customers, 'json');
        // Retourne la réponse JSON
        return new JsonResponse($serializedCustomers, Response::HTTP_OK, [], true);
    }

    public function collectionExist(string $collectionName) : bool
    {
        // instancie un objet Database de Customer
        $database = $this->dm->getDocumentDatabase(Customer::class);
        // je prends la liste des collections existantes
        $collections = $database->listCollections();
        // boucle verifie si nom collection correspond nom collection existante
        foreach ($collections as $collection) {
            if($collection->getName() === $collectionName){
                return true;
            }
        }
        return false;
    }

    public function createCollection(Request $request) {
        // définir le nom de la collection
        $collectionName = 'Customer';
        // si le nom de ma collection ne correspond à une aucune collection existante alors je créé la collection
        if(!$this->collectionExist($collectionName))
        {
            $this->dm->getSchemaManager()->createDocumentCollection($collectionName);
            return new Response ('Collection created successfully');
        } 
        return new Response('Seems this collection already exist');
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

    public function updateCustomer(Request $request): JsonResponse {
        // vérifier si il y a un paramètre de requête ID
        $id = $request->query->get('id');
        if(!$id){
            throw new \InvalidArgumentException('oups something went wrong check your ID');
        }
        $customer = $this->dm->getRepository(Customer::class)->find($id);
        if(!$customer){
            throw new NotFoundHttpException('oups no customer found with id : '. $id);
        }
        $requestDatas = json_decode($request->getContent(), true);
    
        // Mise à jour des données du client si elles sont fournies
        if (isset($requestDatas['firstName'])) {
            $customer->setFirstName($requestDatas['firstName']);
        }
        if (isset($requestDatas['lastName'])) {
            $customer->setLastName($requestDatas['lastName']);
        }
        if (isset($requestDatas['adress'])) {
            $customer->setAdress($requestDatas['adress']);
        }
        if (isset($requestDatas['permitNumber'])) {
            $customer->setPermitNumber($requestDatas['permitNumber']);
        }
        $this->dm->flush();

        $serializeCustomer = $this->serializer->serialize($customer, 'json');
    
        // Retourner les données du client sous forme de tableau associatif pour une meilleure manipulation
        return new JsonResponse($serializeCustomer, 200, [], true);
    }

    public function deleteCustomer(Request $request) : Response {
        $id = $request->query->get('id');
        if(!$id){
            throw new InvalidArgumentException('oups something went wrong check your ID');
        }
        $customer = $this->dm->getRepository(Customer::class)->find($id);
        if (!$customer) {
            throw new InvalidArgumentException('Customer not found for ID ' . $id);
        }
        $this->dm->remove($customer);
        $this->dm->flush();
        return new Response ('operation successfull : customer deleted');
    }

    // recherche client à partir du nom / prénom passé en paramètres au controlleur
    public function getCustomer(Request $request) : JsonResponse {

        $firstName = $request->query->get('firstName');
        $lastName = $request->query->get('lastName');
        if(!$firstName & !$lastName){
            throw new InvalidArgumentException('oups something went wrong with your firstname or lastname');
        }
        $customer = $this->dm->getRepository(Customer::class)->findOneBy(['firstName' => $firstName, 'lastName' => $lastName]);
        if(!$customer){
            throw new NotFoundHttpException('no customer found with firstname : ' . $firstName . 'and lastname : '. $lastName);
        }
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
