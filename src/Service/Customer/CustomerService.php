<?php

namespace App\Service\Customer;
use DateTime;
use App\Entity\Contract;
use App\Document\Customer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Connection;
use InvalidArgumentException;
use App\Repository\ContractRepository;
use App\Service\Contract\ContractService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CustomerService {
    private $dm;
    private $em;
    private $serializer;
    private $connection;
    private $contractRepo;

    public function __construct(DocumentManager $dm, EntityManagerInterface $em, SerializerInterface $serializer, Connection $connection, ContractRepository $contractRepo){
        // initialise les dépendances du customer Service
        $this->dm = $dm;
        $this->em = $em;
        $this->serializer = $serializer;
        $this->connection = $connection;
        $this->contractRepo = $contractRepo;
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

    public function deleteCustomer(string $customerId) 
    {
        // recherche l'objet customer via correspondance à partir de l'ID customer
        // i
        $customer = $this->dm->getRepository(Customer::class)->find($customerId);
        if (!$customer) {
            throw new InvalidArgumentException('Customer not found for ID ' . $customerId);
        }
        $this->dm->remove($customer);
        $this->dm->flush();
        // retourne un tableau json
        return ['message' => 'operation successfull : customer deleted'];
    }

    
    public function getCustomer(string $firstName, string $lastName)
    {
        // recherche client à partir du nom / prénom passé en paramètres au controlleur
        $customer = $this->dm->getRepository(Customer::class)->findOneBy(['firstName' => $firstName, 'lastName' => $lastName]);
        if(!$customer){
            throw new NotFoundHttpException('no customer found with firstname : ' . $firstName . 'and lastname : '. $lastName);
        }
        // retourne un objet php
        return $customer;
    }
        

    public function getContractFromCustomerId(string $customerId)
    {
        // recherche contrat dans le repo contract à l'aide d'une recherche par correspondance avec le customerId
        $contractList = $this->contractRepo->findBy(['customerId' => $customerId]);
        // retourne un objet PHP
        return $contractList;
    }

    public function getCurrentContractsFromCustomer(array $contracts) 
    {
        // initialise l'objet DateTime
        $todaysDate = new DateTime();
        // initialise un tableau vide
        $onGoingContracts = [];
        // boucle sur chaque contrat
        // détermine si contrat est "en cours"
        // contrat est "en cours" si :
        // DATE DEBUT est passée et DATE FIN est à venir
        // si contrat "en cours"
        // ajouter contrat à tableau onGoingContracts
        foreach ($contracts as $contract) {
            $locBeginDatetime = $contract->getLocBeginDateTime();
            $locEndDateTime = $contract->getLocEndDateTime();
            if($locBeginDatetime <= $todaysDate && $todaysDate <= $locEndDateTime){
                $onGoingContracts[] = $contract;
            }
        }
        return $onGoingContracts;
    }

    public function getLateContractsOnAverageByCustomer(ContractService $contractService)
    {
        // à partir d'une liste de TOUS LES CONTRATS
        $contracts = $this->contractRepo->findAll();
        // à partir d'une liste de TOUS LES CLIENTS
        $customers = $this->dm->getRepository(Customer::class)->findAll();
        // initialiser un tableau vide qui accueillera les données mises à jour
        $customersWithAverage = [];
    
        // boucle 1 -> pour chaque client
        // initialise un totalContratsEnRetard = 0;
        // initialise un totalContra = 0;

        foreach ($customers as $customer) {
            $totalLateContracts = 0;
            $totalContracts = 0;
            // boucle 2 -> pour chaque contrat
            foreach ($contracts as $contract) {
                // condition 1 => recherche correspondance entre iD client et customerId du contrat, si correspondance => entrer dans condition suivante
                if($customer->getId() === $contract->getCustomerId()){
                    // si condition 1 respectée
                    // condition 2 => déterminer si contrat est EN RETARD 
                    // contrat en retard si fonction booléenne isLateContract renvoie TRUE
                    if($contractService->isLateContract($contract) == true){
                        // si condition 2 respectée, incrémenter 1 à totalLateContracts
                        $totalLateContracts += 1;
                    }
                    $totalContracts += 1;
                    // si condition 2 pas respectée, incrémenter 1 à totalContract
                } // sortir de la condition 1
            } // sortir de la boucle 2
            
            // le code poursuit la boucle 1 -> pour chaque client
            if($totalContracts > 0) {
                // si le client possède des contrats, calculer le taux de contrat déclarés "en retard" 
                $averageLateContracts = ($totalLateContracts / $totalContracts * 100) . '%';
            } else {
                // si le client ne possède AUCUN contrat, indiquer 0%
                $averageLateContracts = '0%';
            }
            // initialise un tableau avec les infos clients + taux de retard
            $customerDatas = [
                // "id" => $customer->getId(),
                "firstName" => $customer->getFirstName(),
                "lastName" => $customer->getLastName(),
                "adress" => $customer->getAdress(),
                "late_on_average" => $averageLateContracts
            ];
            // ajouter le tableau au tableau vide initialisé en début de fonction
            $customersWithAverage["id".$customer->getId()] = $customerDatas;
            // sortir de la boucle 1
        }
        // retourner une réponse de valeur PHP de type tableau
        return $customersWithAverage;
    }

    public function getContractsGroupByCustomer()
{
    // instancier le QUERY BUILDER
    $qb = $this->em->createQueryBuilder();
    // définir la requête
    // afficher les contrats regroupés par client
    $qb->select('c')
        ->from(Contract::class, 'c')
        ->orderBy('c.customerId', 'ASC');
    $query = $qb->getQuery();
    // obtenir le résultat de la requête
    $contracts = $query->getResult();
    // initialiser un tableau vide
    $contractsByCustomers = [];
    // Regrouper les contrats par client
    foreach ($contracts as $contract) {
        $customerId = $contract->getCustomerId();
        $customerName = 'customer' . $customerId;
        // Vérifier si le client existe déjà dans le tableau, sinon le créer
        if (!isset($contractsByCustomers[$customerName])) {
            $customer = $this->dm->getRepository(Customer::class)->find($customerId);
            $contractsByCustomers[$customerName] = [
                'customer_info' => $customer, // Ajouter les informations sur le client si nécessaire
                'contracts' => [], // Initialiser le tableau des contrats pour ce client
            ];
        }
        // Ajouter le contrat au tableau des contrats du client
        $contractsByCustomers[$customerName]['contracts'][] = $contract;
    }
    return $contractsByCustomers;
}
}
