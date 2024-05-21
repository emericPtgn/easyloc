<?php

namespace App\Service\Customer;
use DateTime;
use App\Entity\Contract;
use App\Document\Customer;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Doctrine\DBAL\Connection;
use InvalidArgumentException;
use Doctrine\DBAL\Schema\Schema;
use App\Repository\ContractRepository;
use App\Service\Contract\ContractService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CustomerService {
    private $dm;
    private $em;
    private $serializer;
    private $connection;
    private $contractRepo;

    public function __construct(DocumentManager $dm, EntityManagerInterface $em, SerializerInterface $serializer, Connection $connection, ContractRepository $contractRepo){
        $this->dm = $dm;
        $this->em = $em;
        $this->serializer = $serializer;
        $this->connection = $connection;
        $this->contractRepo = $contractRepo;
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

    public function createCollection() 
    {
        // définir le nom de la collection
        $collectionName = 'Customer';
        // si le nom de ma collection ne correspond à une aucune collection existante alors je créé la collection
        if(!$this->collectionExist($collectionName))
        {
            $this->dm->getSchemaManager()->createDocumentCollection($collectionName);
            return ['message' => 'Collection created successfully'];
        } 
        return ['message' => 'Seems this collection already exist'];
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

    public function updateCustomer(string $customerId, array $requestDatas)
    {
        // vérifier si il y a un paramètre de requête ID
        $customer = $this->dm->getRepository(Customer::class)->find($customerId);
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
        return $customer;
    }

    public function deleteCustomer($customerId) 
    {
        $customer = $this->dm->getRepository(Customer::class)->find($customerId);
        if (!$customer) {
            throw new InvalidArgumentException('Customer not found for ID ' . $customerId);
        }
        $this->dm->remove($customer);
        $this->dm->flush();
        return ['message' => 'operation successfull : customer deleted'];
    }

    // recherche client à partir du nom / prénom passé en paramètres au controlleur
    public function getCustomer(string $firstName, string $lastName)
    {
        $customer = $this->dm->getRepository(Customer::class)->findOneBy(['firstName' => $firstName, 'lastName' => $lastName]);
        if(!$customer){
            throw new NotFoundHttpException('no customer found with firstname : ' . $firstName . 'and lastname : '. $lastName);
        }
        return $customer;
    }
        

    public function getContractFromCustomerId(string $customerId)
    {
        $contractList = $this->contractRepo->findBy(['customerId' => $customerId]);
        return $contractList;
    }

    public function getCurrentContractsFromCustomer(array $contracts) 
    {
        $todaysDate = new DateTime();
        $onGoingContracts = [];
        foreach ($contracts as $contract) {
            $locBeginDatetime = $contract->getLocBeginDateTime();
            $locEndDateTime = $contract->getLocEndDateTime();
            if($locBeginDatetime <= $todaysDate && $todaysDate <= $locEndDateTime){
                $onGoingContracts[] = $contract;
            }
        }
        if(empty($onGoingContracts)){
            return ('No ongoing contract found');
        }
        return $onGoingContracts;
    }

    public function getLateContractsOnAverageByCustomer(ContractService $contractService)
    {
        $contracts = $this->contractRepo->findAll();
        $customers = $this->dm->getRepository(Customer::class)->findAll();
    
        $customersWithAverage = [];
    
        foreach ($customers as $customer) {
            $totalLateContracts = 0;
            $totalContracts = 0;
    
            foreach ($contracts as $contract) {
                if($customer->getId() === $contract->getCustomerId()){
                    if($contractService->isLateContract($contract) == true){
                        $totalLateContracts += 1;
                    }
                    $totalContracts += 1;
                }
            }
    
            if($totalContracts > 0) {
                $averageLateContracts = ($totalLateContracts / $totalContracts * 100) . '%';
            } else {
                $averageLateContracts = '0%';
            }
    
            $customerDatas = [
                "id" => $customer->getId(),
                "firstName" => $customer->getFirstName(),
                "lastName" => $customer->getLastName(),
                "adress" => $customer->getAdress(),
                "is late on average" => $averageLateContracts
            ];
    
            $customersWithAverage[] = $customerDatas;
        }
    
        return $customersWithAverage;
    }

    public function getContractsByCustomers(Request $request)
{
    $qb = $this->em->createQueryBuilder();

    $qb->select('c')
        ->from(Contract::class, 'c')
        ->orderBy('c.customerId', 'ASC');

    $query = $qb->getQuery();

    $contracts = $query->getResult();

    $contractsByCustomers = [];

    // Regrouper les contrats par client
    foreach ($contracts as $contract) {
        $customerId = $contract->getCustomerId();
        $customerName = 'customer' . $customerId;

        // Vérifier si le client existe déjà dans le tableau, sinon le créer
        if (!isset($contractsByCustomers[$customerName])) {
            $contractsByCustomers[$customerName] = [
                'customer_info' => $contract->getCustomer(), // Ajouter les informations sur le client si nécessaire
                'contracts' => [], // Initialiser le tableau des contrats pour ce client
            ];
        }

        // Ajouter le contrat au tableau des contrats du client
        $contractsByCustomers[$customerName]['contracts'][] = $contract;
    }

    // Sérialiser les résultats en JSON
    $serializedResults = $this->serializer->serialize($contractsByCustomers, 'json', [
        'groups' => ["contract", "billing"]
    ]);

    return new JsonResponse($serializedResults, Response::HTTP_OK, [], true);
}


}
