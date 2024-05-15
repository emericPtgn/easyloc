<?php

namespace App\Service\Contract;
use DateTime;
use App\Entity\Contract;
use Psr\Log\LoggerInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManagerInterface;
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

    public function __construct(EntityManagerInterface $em, SerializerInterface $serializer, Connection $connection, LoggerInterface $logger){
        $this->em = $em;
        $this->serializer = $serializer;
        $this->connection = $connection;
        $this->logger = $logger;
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

    public function getContracts(Request $request) : JsonResponse {
        $this->logger->info('getContracts ACTIVED');
        $customerId = $request->query->get('customerId');
        if($customerId){
            return $this->getContractsFromCustomerId($customerId, $request);
        }
        $contractId = $request->query->get('contractId');
        if(!$contractId){
            throw new \InvalidArgumentException('oups something went wrong, check your contract ID');
        }
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
        $this->logger->info('getContractsFromCustomer ACTIVED');
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
        $this->logger->info('onGoingContract ACTIVED');
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

}