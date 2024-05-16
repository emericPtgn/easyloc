<?php

namespace App\Service\Billing;
use App\Entity\Billing;
use App\Entity\Contract;
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

    public function __construct(EntityManagerInterface $em, SerializerInterface $serializer, Connection $connection, LoggerInterface $logger){
        $this->em = $em;
        $this->serializer = $serializer;
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function createTable(Request $request): Response
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

            return new Response('Table created successfully');
        }

        return new Response('Table already exists');
    }


    public function createBilling(Request $request) 
    {
        $id = $request->query->get('contractId');
        if(!$id){
            return new Response ('oups something went wrong, check your ID :'. $id);
        }
        $contract = $this->em->getRepository(Contract::class)->find($id);
        if(!$contract){
            throw new \InvalidArgumentException('oups something went wrong, no contract found with this ID');
        }
        $billing = new Billing(); 
        $billing->setContract($contract);
        
        $requestDatas = json_decode($request->getContent(), true);
        if(isset($requestDatas['amount'])){
            $billing->setAmount($requestDatas['amount']);
        }
        $this->em->persist($billing);
        $this->em->flush();

        $serializeBilling = $this->serializer->serialize($billing, 'json', [
            'groups' => ['billing'],
        ]);        
        
        return new JsonResponse($serializeBilling, Response::HTTP_OK, [], true);
    }


    public function updateBilling(Request $request) : JsonResponse {
        // récupérer l'ID depuis les paramètres de requête
        $id = $request->query->get('id');
        // rendre exception si pas d'id
        if(!$id){
            throw new \InvalidArgumentException('Oups something went wrong, check your ID');
        }
        // si ID, rechercher billing avec l'ID
        $billing = $this->em->getRepository(Billing::class)->find($id);
        // si pas de billing, rendre exception pas de billing trouvé avec cet ID
        if(!$billing){
            throw new NotFoundHttpException('Oups something went wrong, no billing found with ID : ' . $id);
        }
        // billing trouvé donc je recupère le tableau de donnée du corps de la requête
        $billingDatas = json_decode($request->getContent(), true);
        if(!$billingDatas){
            throw new NotFoundHttpException('no request content, please try again');
        }
        $billing->setAmount($billingDatas['amount']);
        $this->em->persist($billing);
        $this->em->flush();
        
        $serializedBilling = $this->serializer->serialize($billing, 'json', [
            'groups' => ['billing']
        ]);

        return new JsonResponse($serializedBilling, 200, [], true);
}

    public function deleteBilling(Request $request) : Response {
        $id = $request->query->get('id');
        $this->logger->info($id);

        $ids = json_decode($request->getContent(), true);
        if($ids){
            foreach ($ids as $id) {
                $billing = $this->em->getRepository(Billing::class)->find($id);
                $this->em->remove($billing);
                $this->em->flush();
            }
        } else {
            if(!$id){
                throw new \InvalidArgumentException('oups something went wrong with your request, check your ID or try again');
            }
            $billing = $this->em->getRepository(Billing::class)->find($id);
            if(!$billing){
                throw new NotFoundHttpException('no billing found with this id : '. $id);
            }
            $this->em->remove($billing);
            $this->em->flush();
        }
        return new Response ('operation succeed : billing has been delete', 200, []);
    }

    public function getBilling(Request $request) : JsonResponse {
        $contractId = $request->query->get('contractId');
        if($contractId){
            return $this->getBillingFromContractId($contractId, $request);
        }   
        $billingId = $request->query->get('billingId');
        if(!$billingId){
            throw new \InvalidArgumentException('oups something went wrong, please check your ID');
        }
        $billing = $this->em->getRepository(Billing::class)->find($billingId);
        if(!$billing){
            throw new NotFoundHttpException('no billing found with ID : ' . $billingId);
        }
        $serializeBilling = [];
        $data = [
            'id' => $billing->getId(),
            'amount' => $billing->getAmount(),
            'contractId' => $billing->getContract()->getId()
        ];
        $serializeBilling[] = $data;

/*         $serializeBilling = $this->serializer->serialize($serializeBilling, 'json', [
            'groups' => ['billing']
        ]); */

        return new JsonResponse($serializeBilling, 200, [], false);
    }

    public function getBillingFromContractId(string $contractId, Request $request) : JsonResponse
    {
        $contract = $this->em->getRepository(Contract::class)->find($contractId);
        if(!$contract){
            throw new \InvalidArgumentException('no contract found with this ID');
        }
        $billings = $contract->getBillings();
        $serializedBillings = $this->serializer->serialize($billings, 'json', [
            'groups' => ['billing']
        ]);
        return new JsonResponse($serializedBillings, Response::HTTP_OK, [], true);
    }


}