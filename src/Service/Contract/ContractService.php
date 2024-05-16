<?php

namespace App\Service\Contract;
use DateTime;
use App\Entity\Contract;
use App\Document\Vehicle;
use App\Document\Customer;
use Psr\Log\LoggerInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ContractService {
    private $em;
    private $serializer;
    private $connection;
    private $logger;
    private $dm;

    public function __construct(EntityManagerInterface $em, SerializerInterface $serializer, Connection $connection, LoggerInterface $logger, DocumentManager $dm){
        $this->em = $em;
        $this->serializer = $serializer;
        $this->connection = $connection;
        $this->logger = $logger;
        $this->dm = $dm;
    }

    public function createTable(Request $request) : Response {
        $schemaTable = $this->connection->getSchemaManager();
        $tableName = 'contract';
        $tableExist = $schemaTable->tablesExist([$tableName]);
        if(!$tableExist){
            $schema = new Schema();
            $table = $schema->createTable($tableName);
            $table->addColumn('id', 'integer', ['autoincrement' => true]);
            $table->addColumn('vehicleId', 'string', ['length' => 24]);
            $table->addColumn('customerId', 'string', ['length' => 24]);
            $table->addColumn('signDateTime', 'datetime');
            $table->addColumn('locBeginDateTime', 'datetime');
            $table->addColumn('locEndDateTime', 'datetime');
            $table->addColumn('returningDateTime', 'datetime');
            $table->addColumn('price', 'decimal', ['precision' => 10, 'scale' => 2]);
            $table->setPrimaryKey(['id']);
            $table->addForeignKeyConstraint('Vehicle', ['vehicleId'], ['id'] );
            $table->addForeignKeyConstraint('Customer', ['customerId'], ['id'] );

            $queries = $schema->toSql($this->connection->getDatabasePlatform());
            foreach ($queries as $query) {
                $this->connection->executeStatement($queries);
            }
            return new Response ('Table created successfully');
        }
        return new Response ('Table already exist');
    }

    public function createContract(Request $request) : JsonResponse {
        $vehicleId = $request->query->get('vehicleId');
        $customerId = $request->query->get('customerId');
        $requestDatas = json_decode($request->getContent(), true);
        $contract = new Contract();

        if(!$vehicleId){
            throw new \InvalidArgumentException('oups something went wrong with your request and vehicleId');
        } $contract->setVehicleId($vehicleId); 
        if(!$customerId){
            throw new \InvalidArgumentException('oups something went wrong with your request and customerId');
        } $contract->setCustomerId($customerId);

        if(isset($requestDatas['signDateTime'])){
            $signDateTime = new DateTime($requestDatas['signDateTime']);
            $contract->setSignDateTime($signDateTime);
        }

        if(isset($requestDatas['locBeginDateTime'])){
            $locBeginDateTime = new DateTime($requestDatas['locBeginDateTime']);
            $contract->setLocBeginDateTime($locBeginDateTime);
        }

        if(isset($requestDatas['locEndDateTime'])){
            $locEndDateTime = new DateTime($requestDatas['locEndDateTime']);
            $contract->setLocEndDateTime($locEndDateTime);
        }

        if(isset($requestDatas['returningDateTime'])){
            $returningDateTime = new DateTime($requestDatas['returningDateTime']);
            $contract->setReturningDateTime($returningDateTime);
        }

        if(isset($requestDatas['price'])){
            $contract->setPrice($requestDatas['price']);
        }

        $this->em->persist($contract);
        $this->em->flush();
        $serializeContract = $this->serializer->serialize($contract, 'json');
        return new JsonResponse($serializeContract, 200, [], true);
    }   

    public function updateContract(Request $request, LoggerInterface $logger){

        $requestDatas = json_decode($request->getContent(), true);
        $id = $request->query->get('id');
        $contract = $this->em->getRepository(Contract::class)->find($id);
        if(isset($requestDatas['vehicleId'])){
            $contract->setVehicleId($requestDatas['vehicleId']);
        }
        if(isset($requestDatas['customerId'])){
            $contract->setCustomerId($requestDatas['customerId']);
        }
        if(isset($requestDatas['signDateTime'])){
            $signDateTime = new DateTime($requestDatas['signDateTime']);
            $contract->setSignDateTime($signDateTime);
        }
        if(isset($requestDatas['locBeginDateTime'])){
            $locBeginDateTime = new DateTime($requestDatas['locBeginDateTime']);
            $contract->setLocBeginDateTime($locBeginDateTime);
        }
        if(isset($requestDatas['locEndDateTime'])){
            $locEndDateTime = new DateTime($requestDatas['locEndDateTime']);
            $contract->setLocEndDateTime($locEndDateTime);
        }

        if(isset($requestDatas['returningDateTime'])){
            $returningDateTime = new DateTime($requestDatas['returningDateTime']);
            $contract->setReturningDateTime($returningDateTime);
        } else {
            $contract->setReturningDateTime(null);
        }
        if(isset($requestDatas['price'])){
            $contract->setPrice($requestDatas['price']);
        }
        $this->em->persist($contract);
        $this->em->flush();

        $serializeContract = $this->serializer->serialize($contract, 'json');
        return new JsonResponse($serializeContract, 200, [], true);

    }

    public function deleteContract(Request $request) : Response {
        $id = $request->query->get('id');
        if(!$id){
            $response = "oups something went wrong, check your id'";
            return new Response ($response);
        } 
        $contract = $this->em->getRepository(Contract::class)->find($id);
        if(!$contract){
            $response = 'oups something went wrong, no contract found';
            return new Response ($response);
        }
        $this->em->remove($contract);
        $this->em->flush();

        return new Response ('operation success, contract has been deleted', Response::HTTP_OK);

    }

    public function getContracts(Request $request) {
        $customerId = $request->query->get('customerId');
        if($customerId){
            return $this->getContractsFromCustomerId($customerId, $request);
        }

        $vehicleId = $request->query->get('vehicleId');
        if($vehicleId){
            return $this->getContractsFromVehicleId($vehicleId, $request);
        }

        $isLateParam = $request->query->get('isLate');
        if($isLateParam){
            return $this->getLateContracts($request);
        }

        $isPaid = $request->query->get('isPaid');
        if($isPaid){
            $this->logger->info('valeur de ispaid dans GetContract : '. $isPaid);
            return $this->getPaidContracts($request);
        } 
        
        $contractId = $request->query->get('contractId');
        if($contractId){
            return $this->getContractFromContractId($contractId, $request);
        }

        $filterBy = $request->query->get('by');
        if($filterBy == "customer"){
            return $this->getContractsByCustomers($request);
        } elseif($filterBy === "vehicle"){
            return $this->getContractsByVehicles($request);
        }

        $contracts = $this->em->getRepository(Contract::class)->findAll();
        if(!$contracts){
            return new NotFoundHttpException ('no contracts found');
        }
        $serializedContracts = $this->serializer->serialize($contracts, 'json', [
            'groups' => ['contract', 'billing']
        ]);
        return new JsonResponse($serializedContracts, Response::HTTP_OK, [], true);
    }

    public function getContractFromContractId(string $contractId, Request $request) : JsonResponse
    {
        $contract = $this->em->getRepository(Contract::class)->find($contractId);
        if(!$contract){
            throw new NotFoundHttpException('no contract found');
        }
        $serializeContract = $this->serializer->serialize($contract, 'json', [
            'groups' => ['contract', 'billing']
        ]);
        return new JsonResponse($serializeContract, Response::HTTP_OK, [], true);
    }

    public function getContractsFromCustomerId(string $customerId, Request $request)
    {
        $contracts = $this->em->getRepository(Contract::class)->findBy(['customerId' => $customerId]);
        if(!$contracts){
            return new NotFoundHttpException('no contract found with this ID');
        };
        if($request->query->get('ongoingcontracts')){
            return $this->getOngoingContractsFromCustomerId($contracts, $request);
        }
        foreach ($contracts as $contract) {
            $contract->getBillings();
        }
        $serializeContracts = $this->serializer->serialize($contracts, 'json', [
            'groups' => ['contract', 'billing']
        ]);
        return new JsonResponse($serializeContracts, Response::HTTP_OK, [], true);
    }

    public function getOngoingContractsFromCustomerId(array $contracts, Request $request) : JsonResponse 
    {
        $todaysDate = new DateTime();
        $onGoingContracts = [];
        foreach ($contracts as $contract) {
            $locBeginDatetime = $contract->getLocBeginDateTime();
            $locEndDateTime = $contract->getLocEndDateTime();
            if($locBeginDatetime <= $todaysDate && $todaysDate <= $locEndDateTime){
                $this->logger->info('date début du contrat :'. ($locBeginDatetime->format('Y-m-d H:i:s')) . '---' . 'date fin du contrat :'. ($locEndDateTime->format('Y-m-d H:i:s')) . '---' . 'todays date : '. $todaysDate->format('Y-m-d H:i:s'));
                $onGoingContracts[] = $contract;
            }
        }
        if(empty($onGoingContracts)){
            return new Response ('No ongoing contract found');
        }
        $serializeOnGoingContracts = $this->serializer->serialize($onGoingContracts, 'json', [
            'groups' => ['contract', 'billing']
        ]);
        return new JsonResponse($serializeOnGoingContracts, Response::HTTP_OK, [], true);
    }

    public function getLateContracts(Request $request) 
    {
        $contracts = $this->em->getRepository(Contract::class)->findAll();
        if(!$contracts){
            throw new NotFoundHttpException('no contract found');
        }
        $lateContracts = [];
        $todaysDate = new DateTime();

        foreach ($contracts as $contract) {
            $returningDateTime = $contract->getReturningDateTime();
            $locEndDateTime = $contract->getLocEndDateTime();
            $delta = $locEndDateTime->diff($todaysDate);
            $conditionToLate = ($returningDateTime == null && $locEndDateTime < $todaysDate && $delta->h >= 1);
            if ($conditionToLate) {
                $lateContracts[] = $contract;
            }
        }
        
        $serializeLateContracts = $this->serializer->serialize($lateContracts, 'json', [
            'groups' => ['contract', 'billing']
        ]);

        if($request->query->get('startDate') && $request->query->get('endDate')){
            return $this->getLateContractsBetween($request);
        };
        if($request->query->get('by') || $request->query->get('on')){
            return $this->getLateContractsOnBy($request, $lateContracts);
        }

        
        return new JsonResponse($serializeLateContracts, Response::HTTP_OK, [], true);

    }

    public function getPaidContracts(Request $request)
{
    $contracts = $this->em->getRepository(Contract::class)->findAll();
    if(!$contracts){
        throw new NotFoundHttpException('no contract found');
    }
    $filteredContracts = [];
    $isPaid = filter_var($request->query->get('isPaid'), FILTER_VALIDATE_BOOLEAN);
    $this->logger->info("valeur de isPaid dans getPaidContracts: ". $isPaid);

    foreach ($contracts as $contract) {
        $price = $contract->getPrice();
        $billings = $contract->getBillings();
        $totalBilling = 0;
    
        foreach ($billings as $billing) {
            $totalBilling += $billing->getAmount();
        }
    
        if ($isPaid === true && $price == $totalBilling) {
            $filteredContracts[] = $contract;
        } elseif ($isPaid === false && ($price > $totalBilling || $totalBilling == 0)) {
            $filteredContracts[] = $contract;
        }
    }
    
    $serializedContracts = $this->serializer->serialize($filteredContracts, 'json', [
        'groups' => ['contract', 'billing']
    ]);
    return new JsonResponse($serializedContracts, Response::HTTP_OK, [], true);
}

public function getLateContractsBetween(Request $request)
{
    $contracts = $this->em->getRepository(Contract::class)->findAll();
    if(!$contracts){
        throw new NotFoundHttpException('no contract found');
    }
    $intervalDate1 = $request->query->get('startDate');
    $intervalDate1 = new DateTime($intervalDate1);
    $intervalDate2 = $request->query->get('endDate');
    $intervalDate2 = new DateTime($intervalDate2);
    $todaysDate = new DateTime();
    $lateContractsBetween = [];
    foreach ($contracts as $contract){
        $returningDateTime = $contract->getReturningDateTime();
        $locEndDateTime = $contract->getLocEndDateTime();
        if($returningDateTime == null && $locEndDateTime < $todaysDate && $todaysDate->diff($locEndDateTime)->h >= 1 && $intervalDate1 < $locEndDateTime && $locEndDateTime < $intervalDate2){
            $lateContractsBetween[] = $contract;
        } elseif(($returningDateTime) && ($returningDateTime->diff($locEndDateTime)->h >= 1) && ($intervalDate1 < $returningDateTime && $returningDateTime < $intervalDate2)) {
            $lateContractsBetween[] = $contract;
        };
    };

    if($request->query->get('total')){
        return $this->getCountLateContractBetween($lateContractsBetween, $intervalDate1, $intervalDate2);
    }

    $serializedLateContracts = $this->serializer->serialize($lateContractsBetween, 'json', [
        'groups' => ['contract', 'billing']
    ]);

    return new JsonResponse($serializedLateContracts, Response::HTTP_OK, [], true);
}

    public function getCountLateContractBetween($lateContractsBetween, $intervalDate1, $intervalDate2){
        $intervalDate1 = $intervalDate1->format('Y-m-d-d H:i:s');
        $intervalDate2 = $intervalDate2->format('Y-m-d-d H:i:s');
        $countLateContracts = 0;
        foreach ($lateContractsBetween as $lateContracts) {
            $countLateContracts += 1;
        };
        $countObject = [
            "total" => $countLateContracts
        ];
        $serializeObject = $this->serializer->serialize($countObject, 'json');
        return new JsonResponse ($serializeObject, Response::HTTP_OK, [], true);
    }

    public function getLateContractsOnBy(Request $request, $lateContracts){
        $on = $request->query->get('on');
        $by = $request->query->get('by');
        if($on == 'average'){
            if($by == 'customer'){
                return $this->getLateContractsOnAverageByCustomer($request);
            } elseif ($by == 'vehicle'){
                return $this->getLateContractsOnAverageByVehicle($request);
            }
        };
    }

    public function getLateContractsOnAverageByCustomer(Request $request)
{
    $contracts = $this->em->getRepository(Contract::class)->findAll();
    if(!$contracts){
        throw new NotFoundHttpException('no contract found');
    }
    $customers = $this->dm->getRepository(Customer::class)->findAll();
    if(!$customers){
        throw new NotFoundHttpException('no customers found');
    }
    $customersWithAverage = [];

    foreach ($customers as $customer) {
        $totalLateContracts = 0;
        $totalContracts = 0;

        foreach ($contracts as $contract) {
            if($customer->getId() === $contract->getCustomerId()){
                if($this->isLateContract($contract) == true){
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

    $serializeCustomersWithAverage = $this->serializer->serialize($customersWithAverage, 'json');

    return new JsonResponse($serializeCustomersWithAverage, Response::HTTP_OK, [], true);
}


    public function isLateContract(Contract $contract){
        $returningDateTime = $contract->getReturningDateTime();
        $locEndDateTime = $contract->getLocEndDateTime();
        $now = new DateTime();
        $isLate = false;
        if( (is_null($returningDateTime)) && ($now->diff($locEndDateTime)->h >= 1) && ($now > $locEndDateTime) ){
            $isLate = true;
            return $isLate;
        } elseif ( ($returningDateTime) && ($returningDateTime->diff($locEndDateTime)->h >= 1) ){
            $isLate = true;
            return $isLate;
        } else { 
            return $isLate;
        }
    }

    public function getContractsFromVehicleId(string $vehicleId, Request $request)
    {
        $contracts = $this->em->getRepository(Contract::class)->findBy(['vehicleId' => $vehicleId]);
        if(!$contracts){
            throw new NotFoundHttpException('no contracts found');
        }
        $serializedContracts = $this->serializer->serialize($contracts, 'json', ['groups' => ['contract', 'billing']]);
        return new JsonResponse($serializedContracts, Response::HTTP_OK, [], true);
    }

    public function getLateContractsOnAverageByVehicle($request)
{
    $contracts = $this->em->getRepository(Contract::class)->findAll();
    $vehicles = $this->dm->getRepository(Vehicle::class)->findAll();

    if (empty($contracts)) {
        throw new NotFoundHttpException('No contracts found');
    }
    if (empty($vehicles)) {
        throw new NotFoundHttpException('No vehicles found');
    }

    $vehiclesWithAverageTimeLate = [];

    foreach ($vehicles as $vehicle) {
        $totalMinutes = 0;
        $totalLateContracts = 0;
        $todayDate = new DateTime();

        foreach ($contracts as $contract) {
            if ($vehicle->getId() === $contract->getVehicleId()) {
                $locEndDateTime = $contract->getLocEndDateTime();
                $returningDateTime = $contract->getReturningDateTime();

                if ($this->isLateContract($contract)) {
                    if (is_null($returningDateTime)) {
                        $late = $todayDate->diff($locEndDateTime)->format("%a:%H:%I");
                    } else {
                        $late = $returningDateTime->diff($locEndDateTime)->format("%a:%H:%I");
                    }

                    $lateMinutes = $this->convertTimeToMinutes($late);
                    $totalMinutes += $lateMinutes;
                    $totalLateContracts++;
                }
            }
        }

        $averageTimeLate = $totalLateContracts > 0 ? $totalMinutes / $totalLateContracts : 0;

        $datasVehicles = [
            "id" => $vehicle->getId(),
            "informations" => $vehicle->getInformations(),
            "plateNumber" => $vehicle->getPlateNumber(),
            "km" => $vehicle->getKm(),
            "average late" => $this->convertMinutesToTime($averageTimeLate)
        ];

        $vehiclesWithAverageTimeLate[] = $datasVehicles;
    }

    $serializedAverageLateVehicles = $this->serializer->serialize($vehiclesWithAverageTimeLate, 'json');
    return new JsonResponse($serializedAverageLateVehicles, Response::HTTP_OK, [], true);
}

    


private function convertTimeToMinutes($time)
{
    list($days, $hours, $minutes) = explode(':', $time);
    $days = (int)$days;
    $hours = (int)$hours;
    $minutes = (int)$minutes;
    return ($days * 24 * 60) + ($hours * 60) + $minutes;
}

private function convertMinutesToTime($minutes)
{
    $hours = floor($minutes / 60);
    $minutes = $minutes % 60;
    return sprintf('%02d:%02d', $hours, $minutes);
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
            $contractsByCustomers[$customerName] = [];
        }

        // Ajouter le contrat au tableau du client
        $contractsByCustomers[$customerName][] = $contract;
    }

    // Sérialiser les résultats en JSON
    $serializedResults = $this->serializer->serialize($contractsByCustomers, 'json', [
        'groups' => ["contract", "billing"]
    ]);

    return new JsonResponse($serializedResults, Response::HTTP_OK, [], true);
}





public function getContractsByVehicles(Request $request)
{
    $qb = $this->em->createQueryBuilder();

    $qb->select('c')
        ->from(Contract::class, 'c')
        ->orderBy('c.vehicleId', 'ASC');

    $query = $qb->getQuery();

    $contracts = $query->getResult();

    $contractsByVehicles = [];

    // Regrouper les contrats par vehicle
    foreach ($contracts as $contract) {
        $vehicleId = $contract->getVehicleId();
        $vehicleName = 'vehicle' . $vehicleId;

        // Vérifier si le vehicle existe déjà dans le tableau, sinon le créer
        if (!isset($contractsByVehicles[$vehicleName])) {
            $contractsByVehicles[$vehicleName] = [];
        }

        // Ajouter le contrat au tableau du client
        $contractsByVehicles[$vehicleName][] = $contract;
    }

    // Sérialiser les résultats en JSON
    $serializedResults = $this->serializer->serialize($contractsByVehicles, 'json', [
        'groups' => ["contract", "billing"]
    ]);

    return new JsonResponse($serializedResults, Response::HTTP_OK, [], true);
}

}