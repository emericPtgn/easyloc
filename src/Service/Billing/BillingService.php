<?php

namespace App\Service\Billing;
use App\Entity\Billing;
use App\Entity\Contract;
use App\Repository\BillingRepository;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BillingService {
    private $em;
    private $serializer;
    private $connection;
    private $logger;
    private $billingRepo;

    public function __construct(EntityManagerInterface $em, SerializerInterface $serializer, 
    Connection $connection, LoggerInterface $logger, BillingRepository $billingRepo){
        $this->em = $em;
        $this->serializer = $serializer;
        $this->connection = $connection;
        $this->logger = $logger;
        $this->billingRepo = $billingRepo;
    }

    public function createTable()
    {
        $schemaManager = $this->connection->getSchemaManager();
        $tableName = 'billing';

        if (!$schemaManager->tablesExist([$tableName])) {
            $schema = new Schema();
            $table = $schema->createTable($tableName);

            // Ajoutez des colonnes à votre table
            $table->addColumn('id', 'integer', ['autoincrement' => true]);
            $table->addColumn('contractId', 'integer');
            $table->addColumn('amount', 'decimal', ['precision' => 10, 'scale' => 2]);
            // Ajoutez d'autres colonnes selon vos besoins

            // Définissez les contraintes
            $table->setPrimaryKey(['id']);
            $table->addForeignKeyConstraint('contract', ['contract_id'], ['id'], [], 'FK_contract_id');

            // Exécutez le schéma pour créer la table
            $queries = $schema->toSql($this->connection->getDatabasePlatform());
            foreach ($queries as $query) {
                $this->connection->executeStatement($query);
            }

            return ('Table created successfully');
        }

        return ('Table already exists');
    }


    public function createBilling($idContrat, $montant) 
    {

        $contract = $this->em->getRepository(Contract::class)->find($idContrat);
        if(!$contract){
            throw new \InvalidArgumentException('oups something went wrong, no contract found with this ID');
        }
        $billing = new Billing(); 
        $billing->setContract($contract);
        $billing->setAmount($montant);
        $this->em->persist($billing);
        $this->em->flush();

        return $billing;
    }


    public function updateBilling(string $billingId, string $amount)
    {
        $billing = $this->billingRepo->find($billingId);
        $billing->setAmount($amount);
        $this->em->persist($billing);
        $this->em->flush();
        return $billing;
    }

    public function deleteBilling($billingId)
    {
        try {
            $billing = $this->billingRepo->find($billingId);
            $this->em->remove($billing);
            $this->em->flush();
        } catch (\Throwable $th) {
            throw $th;
        }
        return $billing;
    }

    public function getBilling(string $billingId)
    {
        try {
            $billing = $this->billingRepo->find($billingId);
        } catch (\Throwable $th) {
            throw $th;
        }
        return $billing;
    }


}