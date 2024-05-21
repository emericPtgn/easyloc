<?php

namespace App\Service\Vehicle;
use App\Service\Contract\ContractService;
use DateTime;
use App\Entity\Contract;
use App\Document\Vehicle;
use App\Service\Security\ApiTokenService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Collections;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class VehicleService {
    private $dm;
    private $serializer;
    private $em;
    private $token;
    private $contractService;
    
    public function __construct(DocumentManager $dm, EntityManagerInterface $em, SerializerInterface $serializer, ApiTokenService $token, ContractService $contractService){
        $this->dm = $dm;
        $this->em = $em;
        $this->serializer = $serializer;
        $this->token = $token;
        $this->contractService = $contractService;
    }

    public function createVehicle(array $requestDatas)
    {
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
        $this->dm->persist($vehicle);
        $this->dm->flush();
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
        $vehicle = $this->dm->getRepository(Vehicle::class)->find($vehicleId);
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
        $this->dm->flush();
        return $vehicle;
    }

    public function deleteVehicle(string $vehicleId)
    { 
        $vehicle = $this->dm->getRepository(Vehicle::class)->find($vehicleId);
        if(!$vehicle){
            throw new \InvalidArgumentException('Vehicle not found for ID :' . $vehicleId);
        }
        $this->dm->remove($vehicle);
        $this->dm->flush();

        return ['message' => 'operation succed, the vehicle ' . $vehicleId. 'has been deleted'];
    }

    public function getVehicle(string $plateNumber)
    {
        $vehicle = $this->dm->getRepository(Vehicle::class)->findOneBy(['plateNumber' => $plateNumber]);
        return $vehicle;
    }

    public function countVehicle($km)
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
        $vehicles = $query->execute();
        return $vehicles;
    }
    
    public function getLateTimeOnAverageByVehicle()
{
    $contracts = $this->em->getRepository(Contract::class)->findAll();
    $vehicles = $this->dm->getRepository(Vehicle::class)->findAll();

    if (empty($contracts)) {
        throw new NotFoundHttpException('No contracts found');
    } if (empty($vehicles)) {
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

                if ($this->contractService->isLateContract($contract)) {
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
            "is late on average (h:m)" => $this->convertMinutesToTime($averageTimeLate)
        ];

        $vehiclesWithAverageTimeLate[] = $datasVehicles;
    }
    return $vehiclesWithAverageTimeLate;
}

public function getContractsFromVehicleId(string $vehicleId)
{
    $contracts = $this->em->getRepository(Contract::class)->findBy(['vehicleId' => $vehicleId]);
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