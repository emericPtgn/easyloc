<?php

namespace App\Service\Contract;
use App\Repository\ContractRepository;
use DateTime;
use App\Entity\Contract;
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
        // initialise les dépendances du service
        $this->em = $em;
        $this->serializer = $serializer;
        $this->connection = $connection;
        $this->logger = $logger;
        $this->dm = $dm;
        $this->contractRepo = $contractRepo;
    }

    public function createTable() 
    {
        // à partir de la connection indiquée au doctrine.yaml, je vérifie pour cette connexion si la table contract existe
        $schemaTable = $this->connection->getSchemaManager();
        $tableName = 'contract';
        $tableExist = $schemaTable->tablesExist([$tableName]);
        // créer la table si elle n 'existe pas 
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
            // appelle la méthode toSql sur l'objet schema => renvoi un tableau contenant les requêtes nécessaires pour créer  le schéma
            // pour la connexion par défault indiquée au doctrine.yaml
            foreach ($queries as $query) {
                // execute pour cette connexion le tbleau de requête $queries
                // retourne réponse succès 
                $this->connection->executeStatement($queries);
            }
            return ('Table created successfully');
        }
        // retourne réponse si table existe dEJA
        return ('Table already exist');
    }

    public function createContract(string $vehicleId, string $customerId, array $contractDatas) 
    {
        // paramètres transmis par le controlleur
        // vérifie si les champs du tableau $contractDatas sont null
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
        // persiste les données
        $this->em->persist($contract);
        $this->em->flush();
        // retourne l'objet contrat
        return $contract;
    }   


    public function updateContract(string $contractId, array $updateContent)
    {
        // recherche l'enregistrement à mettre à jour 
        // si un champs du tableau updateContent est vide, passer au champs suivant 
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
        // persiste les données
        $this->em->persist($contract);
        $this->em->flush();
        // retourne un objet contrat
        return $contract;
    }

    public function deleteContract(string $contractId)
    {
        // essaie : recherche le contrat à partir contractId - supprime ce contrat - persiste le changement
        try {
            $contract = $this->contractRepo->find($contractId);
            $this->em->remove($contract);
            $this->em->flush();
            return ('contract ID ' . $contractId . ' delete successfully');
        } // sinon affiche l'erreur  
        catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getContract(string $contractId) 
    {
        // recherche ce contrat et affiche ce contrat
        $contract = $this->contractRepo->find($contractId);
        return $contract;
    }

    public function getLateContracts() 
    {
        // prends la liste de TOUS les contrats
        // prends la date du jour
        // pour chaque contrat, si il est déclaré en retard (isLateContrat => true), alors ajouter contrat au tableau lateContracts
        $contracts = $this->contractRepo->findAll();
        $lateContracts = [];
        $todaysDate = new DateTime();
        foreach ($contracts as $contract) {
            if ($this->isLateContract($contract)) {
                $lateContracts[] = $contract;
            }
        }
        // retourne le tableau 
        return $lateContracts;
    }

    public function isLateContract(Contract $contract){
        // fonction d'assistance -> prends un objet contrat en prop
        $returningDateTime = $contract->getReturningDateTime();
        $locEndDateTime = $contract->getLocEndDateTime();
        $now = new DateTime();
        $isLate = false;
        // un contrat est en retard SI (2 options)
       
        if(  // - sa date de retour est null + date de fin du contrat est passée + delta au moins de 1h entre date/heure actuelle v
            (is_null($returningDateTime)) && ($now->diff($locEndDateTime)->h >= 1) && ($now > $locEndDateTime) ){
            $isLate = true;
            return $isLate;
        } elseif   // - date de retour non null + delta de au moins 1h entre date/heure actuelle et date de retour
        ( ($returningDateTime) && ($returningDateTime->diff($locEndDateTime)->h >= 1) ){
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
        // détermine si un contrat est payé 
        // 1) calcul somme totale des factures 
        // 2) calcul reste à payer (tarif contrat - somme totale facture)
        // 3) si reste à payer == 0 alors payé
        // ** affiche reste à payer le cas échéant **
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
        // initialiser tableau vide (répertorie les contrat non payés)
        // boucler sur liste avec tous les contrats
        // si function boolen retourne FALSE alors ajouter contrat au tableau
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
    // initialise tableau vide (repertorie les contrat en retard (locations) sur une période donnée
    // si fonction booléenne retourne true alors ajouter contrat au tableau
    // retourner le compte du nombre de contrat en retart
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

}