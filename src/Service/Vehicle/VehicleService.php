<?php

namespace App\Service\Vehicle;
use App\Service\Contract\ContractService;
use DateTime;
use App\Entity\Contract;
use App\Document\Vehicle;
use App\Service\Security\ApiTokenService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class VehicleService {
    private $dm;
    private $serializer;
    private $em;
    private $token;
    private $contractService;
    
    public function __construct(DocumentManager $dm, EntityManagerInterface $em, SerializerInterface $serializer, ApiTokenService $token, ContractService $contractService){
        // initialise le constructeur du service vehicle
        $this->dm = $dm;
        $this->em = $em;
        $this->serializer = $serializer;
        $this->token = $token;
        $this->contractService = $contractService;
    }

    public function createVehicle(array $requestDatas)
    {
        // créé un nouvel objet vehicle
        // verifie si les champs sont non nulls
        // met à jour les prop du vheicle si les champs comportent une valeur
        $vehicle = new Vehicle();
        if(isset($requestDatas['informations'])){
            $vehicle->setInformations($requestDatas['informations']);
        }
        if(isset($requestDatas['km'])){
            $vehicle->setKm($requestDatas['km']);
        }
        if(isset($requestDatas['plateNumber'])){
            $vehicle->setPlateNumber($requestDatas['plateNumber']);
        }
        // persiste le vehicle
        // enregistre 
        $this->dm->persist($vehicle);
        $this->dm->flush();
        // retourne objet php
        return $vehicle;
    }
        
        public function collectionExists(string $collectionName): bool
    {
        $database = $this->dm->getDocumentDatabase(Vehicle::class);
        // Obtenir la liste des collections dans la base de données
        $collections = $database->listCollections();

        // Parcourir les collections pour vérifier si la collection spécifiée existe déjà
        foreach ($collections as $collectionInfo) {
            if ($collectionInfo->getName() === $collectionName) {
                return true; // La collection existe déjà
            }
        }

        return false; // La collection n'existe pas
    }

    public function createCollection()
    {
        $collectionName = 'Vehicle';
        if(!$this->collectionExists($collectionName)){
            $this->dm->getSchemaManager()->createDocumentCollection($collectionName);
            return ['message' => 'Collection created successfully'];
        } else {
            // Collection déjà existante, pas besoin de créer une nouvelle collection
            return ['message' => 'Collection already exists'];
        }
    }

    public function updateVehicle(string $vehicleId, array $vehicleDatas)
    {
        // rechercher vehicule dans le repo vehicle par recherche par correspondance en utilisant l'id vehicle
        $vehicle = $this->dm->getRepository(Vehicle::class)->find($vehicleId);
        // si vehicule trouvé alros mettre à jour ses prop
        if (!$vehicle) {
            throw new \InvalidArgumentException('Vehicle not found for ID ' . $vehicleId);
        }
        if(isset($vehicleDatas['informations'])){
            $vehicle->setInformations($vehicleDatas['informations']);
        }
        if(isset($vehicleDatas['km'])){
            $vehicle->setKm($vehicleDatas['km']);
        }
        if(isset($vehicleDatas['plateNumber'])){
            $vehicle->setPlateNumber($vehicleDatas['plateNumber']);
        }
        // enregistrer le changements
        $this->dm->flush();
        // retourner l'objet mis à jour
        return $vehicle;
    }

    public function deleteVehicle(string $vehicleId)
    { 
        // recherche dans le repo vehicule le document correspondand via recherche par correspondance avec l'id vehicule
        $vehicle = $this->dm->getRepository(Vehicle::class)->find($vehicleId);
        if(!$vehicle){
            throw new \InvalidArgumentException('Vehicle not found for ID :' . $vehicleId);
        }
        // si vehicule trouvé alors supprimer vehicule
        $this->dm->remove($vehicle);
        // enregistrer les changements
        $this->dm->flush();
        // reoturner un message 
        return 'vehicle deleted';
    }

    public function getVehicle(string $plateNumber)
    {
        // recherche dans le repo vehicule le document dont la valeur de la prop plateNumber correspond à la valeur de la prop en paramètre du
        $vehicle = $this->dm->getRepository(Vehicle::class)->findOneBy(['plateNumber' => $plateNumber]);
        // retourne le vehicule
        return $vehicle;
    }

    public function countVehicle($km): int
{
    // Vérifier si la valeur du paramètre est numérique
    if (!is_numeric($km)) {
        throw new \InvalidArgumentException('The filter value must be numeric.');
    }
    // Convertir la valeur en entier
    $filterValue = (int)$km;
    
    // Utiliser la valeur filtrée pour rechercher les véhicules
    $qb = $this->dm->createQueryBuilder(Vehicle::class)
        ->field('km')->gte($km);
    $query = $qb->getQuery();
    
    // Exécuter la requête et obtenir le curseur
    $cursor = $query->execute();
    
    // Compter le nombre de véhicules
    $count = $cursor->count();
    
    return $count;
}


    
    public function getLateTimeOnAverageByVehicle()
{
    // obtient la liste de TOUS LES CONTRATS
    $contracts = $this->em->getRepository(Contract::class)->findAll();
    // obtient la liste de TOUS LES VEHICUELS
    $vehicles = $this->dm->getRepository(Vehicle::class)->findAll();

    // si une des 2 liste est vide renvoyer un msg 
    if (empty($contracts)) {
        throw new NotFoundHttpException('No contracts found');
    } if (empty($vehicles)) {
        throw new NotFoundHttpException('No vehicles found');
    }

    // initialiser tableau vide qui accueillera les données finales
    $vehiclesWithAverageTimeLate = [];
    // boucle 1 : pour chaque vehicule
    foreach ($vehicles as $vehicle) {
        // initialiser totalMinute et totalLateContract à ZERO
        $totalMinutes = 0;
        $totalLateContracts = 0;
        // obtenir la valeur datetime courante
        $todayDate = new DateTime();

        // boucle 2 : pour chaque contrat
        foreach ($contracts as $contract) {
            // condition 1 : si l'id du vehicule === idVehicule du contrat on entre dans la condition
            if ($vehicle->getId() === $contract->getVehicleId()) {
                // obtenir les dates de fin du contrat et retour du vehicule
                $locEndDateTime = $contract->getLocEndDateTime();
                $returningDateTime = $contract->getReturningDateTime();
                // condition 2 : si le contrat est en retard, on calcule l'écart de temp entre la date de fin du contrat et la date de retour du vheicule
                // initialiser "late" avec la valeur obtenue
                if ($this->contractService->isLateContract($contract)) {
                    if (is_null($returningDateTime)) {
                        $late = $todayDate->diff($locEndDateTime)->format("%a:%H:%I");
                    } else {
                        $late = $returningDateTime->diff($locEndDateTime)->format("%a:%H:%I");
                    }
                    // convertir late en minutes
                    $lateMinutes = $this->convertTimeToMinutes($late);
                    // ajouter la valeur de late en minute au total des minutes en retard pour ce vehicule
                    $totalMinutes += $lateMinutes;
                    // incrémenter de 1 le nombre total de contrat pour ce vehicule
                    // valeur nécessaire pour calculer la moyenne
                    $totalLateContracts++;
                    // sortir de la condition 2
                } 
                // sortir de la condition 1
            }
            // sortir de la boucle 2 
        } 

        // poursuite de la boucle 1 - pour chaque vbehicule
        // si le vehicule a déjà été en retard, alors initialiser averageTimeLate avec le total des minutes en retard, sinon ajotuer 0
        $averageTimeLate = $totalLateContracts > 0 ? $totalMinutes / $totalLateContracts : 0;

        // initialiser un tableau avec la valeur des prop du vehicule
        // ajouter la moyenne de minutes en retard 
        $datasVehicles = [
            "id" => $vehicle->getId(),
            "informations" => $vehicle->getInformations(),
            "plateNumber" => $vehicle->getPlateNumber(),
            "km" => $vehicle->getKm(),
            "late_on_average" => $this->convertMinutesToTime($averageTimeLate)
        ];
        // ajouter le tableau du vehicule au tableau principal qui répertorie tous les vehicuels utilisés en location
        $vehiclesWithAverageTimeLate[] = $datasVehicles;
        // sortir de la boucle principale
    }
    // retourner objet PHP de type tableau
    return $vehiclesWithAverageTimeLate;
}

public function getContractsFromVehicleId(string $vehicleId)
{
    // recherche dans le repo contrat à partir d'une correspondance au vehicleId
    $contracts = $this->em->getRepository(Contract::class)->findBy(['vehicleId' => $vehicleId]);
    // retourne le résultat de la recherche
    return $contracts;
}

public function getContractsGroupByVehicle()
{
    // Récupérer tous les contrats
    $qb = $this->em->createQueryBuilder();
    $qb->select('c')
        ->from(Contract::class, 'c')
        ->orderBy('c.vehicleId', 'ASC');
    $query = $qb->getQuery();
    $contracts = $query->getResult();

    // Initialiser un tableau pour stocker les contrats par véhicule
    $contractsByVehicles = [];

    // Regrouper les contrats par véhicule
    foreach ($contracts as $contract) {
        $vehicleId = $contract->getVehicleId();

        // Vérifier si le véhicule existe déjà dans le tableau, sinon le créer
        if (!isset($contractsByVehicles[$vehicleId])) {
            $contractsByVehicles[$vehicleId] = [];
        }

        // Ajouter le contrat au tableau du véhicule
        $contractsByVehicles[$vehicleId][] = $contract;
    }

    return $contractsByVehicles;
}


public function convertTimeToMinutes($time)
{
    $parts = explode(':', $time);

    if (count($parts) === 2) {
        // Format HH:MM
        list($hours, $minutes) = $parts;
        $days = 0; // Pas de jours dans ce format
    } elseif (count($parts) === 3) {
        // Format DD:HH:MM
        list($days, $hours, $minutes) = $parts;
    } else {
        throw new \InvalidArgumentException("Invalid time format. Expected HH:MM or DD:HH:MM.");
    }

    $days = (int)$days;
    $hours = (int)$hours;
    $minutes = (int)$minutes;

    return ($days * 24 * 60) + ($hours * 60) + $minutes;
}


public function convertMinutesToTime($minutes)
{
    // fonction converti une valeur en minute en valeur heure / minute
    $hours = floor($minutes / 60);
    $minutes = $minutes % 60;
    return sprintf('%02d:%02d', $hours, $minutes);
}

}