<?php

namespace App\Service\Contract;
use App\Repository\ContractRepository;
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
    private $contractRepo;

    public function __construct(EntityManagerInterface $em, SerializerInterface $serializer, 
    Connection $connection, LoggerInterface $logger, DocumentManager $dm, ContractRepository $contractRepo){
        $this->em = $em;
        $this->serializer = $serializer;
        $this->connection = $connection;
        $this->logger = $logger;
        $this->dm = $dm;
        $this->contractRepo = $contractRepo;
    }

    public function createTable() 
    {
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
            return ('Table created successfully');
        }
        return ('Table already exist');
    }

    public function createContract(string $vehicleId, string $customerId, array $contractDatas) 
    {
        $contract = new Contract();
        $contract->setCustomerId($customerId);
        $contract->setVehicleId($vehicleId);
        if(isset($contractDatas['signDateTime'])){
            $signDateTime = new DateTime($contractDatas['signDateTime']);
            $contract->setSignDateTime($signDateTime);
        }
        if(isset($contractDatas['locBeginDateTime'])){
            $locBeginDateTime = new DateTime($contractDatas['locBeginDateTime']);
            $contract->setLocBeginDateTime($locBeginDateTime);
        }
        if(isset($contractDatas['locEndDateTime'])){
            $locEndDateTime = new DateTime($contractDatas['locEndDateTime']);
            $contract->setLocEndDateTime($locEndDateTime);
        }
        if(isset($contractDatas['returningDateTime'])){
            $returningDateTime = new DateTime($contractDatas['returningDateTime']);
            $contract->setReturningDateTime($returningDateTime);
        }
        if(isset($contractDatas['price'])){
            $contract->setPrice($contractDatas['price']);
        }
        $this->em->persist($contract);
        $this->em->flush();
        return $contract;
    }   

    public function updateContract(string $contractId, array $updateContent){
        $contract = $this->contractRepo->find($contractId);
        if(isset($updateContent['vehicleId'])){
            $contract->setVehicleId($updateContent['vehicleId']);
        }
        if(isset($updateContent['customerId'])){
            $contract->setCustomerId($updateContent['customerId']);
        }
        if(isset($updateContent['signDateTime'])){
            $signDateTime = new DateTime($updateContent['signDateTime']);
            $contract->setSignDateTime($signDateTime);
        }
        if(isset($updateContent['locBeginDateTime'])){
            $locBeginDateTime = new DateTime($updateContent['locBeginDateTime']);
            $contract->setLocBeginDateTime($locBeginDateTime);
        }
        if(isset($updateContent['locEndDateTime'])){
            $locEndDateTime = new DateTime($updateContent['locEndDateTime']);
            $contract->setLocEndDateTime($locEndDateTime);
        }

        if(isset($updateContent['returningDateTime'])){
            $returningDateTime = new DateTime($updateContent['returningDateTime']);
            $contract->setReturningDateTime($returningDateTime);
        } else {
            $contract->setReturningDateTime(null);
        }
        if(isset($updateContent['price'])){
            $contract->setPrice($updateContent['price']);
        }
        $this->em->persist($contract);
        $this->em->flush();
        return $contract;
    }

    public function deleteContract(Request $request, string $contractId)
    {
        try {
            $contract = $this->contractRepo->find($contractId);
            $this->em->remove($contract);
            $this->em->flush();
            return ('contract ID ' . $contractId . ' delete successfully');
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getContract(string $contractId) 
    {
        $contract = $this->contractRepo->find($contractId);
        return $contract;
    }

    public function getLateContracts() 
    {
        $contracts = $this->contractRepo->findAll();
        $lateContracts = [];
        $todaysDate = new DateTime();
        foreach ($contracts as $contract) {
            if ($this->isLateContract($contract)) {
                $lateContracts[] = $contract;
            }
        }
/*         if($request->query->get('by') || $request->query->get('on')){
            return $this->getLateContractsOnBy($request, $lateContracts);
        }  */
        return $lateContracts;
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

    public function getBillingsFromContractId(string $contractId)
    {
        $contract = $this->contractRepo->find($contractId);
        $billings = $contract->getBillings();
        return $billings;
    }


    public function checkContractIsPaid(string $contractId)
    {
        $contract = $this->contractRepo->find($contractId);
        if (!$contract) {
            throw new \Exception('Contract not found');
        }

        $billings = $contract->getBillings();
        $totalBilling = 0;
        $contractPrice = $contract->getPrice();

        foreach ($billings as $billing) {
            $totalBilling += $billing->getAmount();
        }

        $toPay = $contractPrice - $totalBilling;
        $isPaid = $toPay == 0;

        return [
            'isPaid' => $isPaid,
            'remainingAmount' => $isPaid ? 0 : $toPay
        ];
    }

    public function getUnpaidContracts()
    {
        $contracts = $this->contractRepo->findAll();
        $unPaidContracts = [];

        foreach ($contracts as $contract) {
            $contractId = $contract->getId();
            $testIsPaid = $this->checkContractIsPaid($contractId);
            if ($testIsPaid['isPaid'] == false) {
                $unPaidContracts[] = $testIsPaid;
            }
        };
        
        return $unPaidContracts;
    }


public function countLateContractBetween(string $intervalDate1, string $intervalDate2)
{
    $contracts = $this->contractRepo->findAll();
    $intervalDate1 = new DateTime($intervalDate1);
    $intervalDate2 = new DateTime($intervalDate2);
    $lateContractsBetween = [];
    foreach ($contracts as $contract){
        if($this->isLateContract($contract)){
            $lateContractsBetween[] = $contract;
        }
    };
    return count($lateContractsBetween);
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

}

