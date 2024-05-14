<?php

namespace App\Service\Contract;
use App\Entity\Contract;
use Psr\Log\LoggerInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\DecimalType;
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

    public function __construct(EntityManagerInterface $em, SerializerInterface $serializer, Connection $connection){
        $this->em = $em;
        $this->serializer = $serializer;
        $this->connection = $connection;
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
            $signDateTime = new \DateTime($requestDatas['signDateTime']);
            $contract->setSignDateTime($signDateTime);
        }

        if(isset($requestDatas['locBeginDateTime'])){
            $locBeginDateTime = new \DateTime($requestDatas['locBeginDateTime']);
            $contract->setLocBeginDateTime($locBeginDateTime);
        }

        if(isset($requestDatas['locEndDateTime'])){
            $locEndDateTime = new \DateTime($requestDatas['locEndDateTime']);
            $contract->setLocEndDateTime($locEndDateTime);
        }

        if(isset($requestDatas['returningDateTime'])){
            $returningDateTime = new \DateTime($requestDatas['returningDateTime']);
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
            $signDateTime = new \DateTime($requestDatas['signDateTime']);
            $contract->setSignDateTime($signDateTime);
        }
        if(isset($requestDatas['locBeginDateTime'])){
            $locBeginDateTime = new \DateTime($requestDatas['locBeginDateTime']);
            $contract->setLocBeginDateTime($locBeginDateTime);
        }
        if(isset($requestDatas['locEndDateTime'])){
            $locEndDateTime = new \DateTime($requestDatas['locEndDateTime']);
            $contract->setLocEndDateTime($locEndDateTime);
        }

        if(isset($requestDatas['returningDateTime'])){
            $returningDateTime = new \DateTime($requestDatas['returningDateTime']);
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

    public function getContract(Request $request) : JsonResponse {
        $id = $request->query->get('id');
        if(!$id){
            throw new \InvalidArgumentException('oups something went wrong, check your contract ID');
        }
        $contract = $this->em->getRepository(Contract::class)->find($id);
        if(!$contract){
            throw new NotFoundHttpException('no contract found');
        }
        $serializeContract = $this->serializer->serialize($contract, 'json');
        
        return new JsonResponse($serializeContract, Response::HTTP_OK, [], true);
    }

}