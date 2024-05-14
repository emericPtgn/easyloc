<?php

namespace App\Service\Vehicle;
use App\Document\Vehicle;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Collections;


class VehicleService {
    private $dm;
    private $serializer;
    
    public function __construct(DocumentManager $dm, SerializerInterface $serializer){
        $this->dm = $dm;
        $this->serializer = $serializer;
    }

    public function createVehicle(Request $request) : JsonResponse {
        $requestDatas = json_decode($request->getContent(), true);
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

        $serializeVehicle = $this->serializer->serialize($vehicle, 'json');
        return new JsonResponse($serializeVehicle, 200, [], true);
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

    public function createCollection(Request $request)
    {
        $collectionName = 'Vehicle';
        if(!$this->collectionExists($collectionName)){
            $this->dm->getSchemaManager()->createDocumentCollection($collectionName);
            return new Response('Collection created successfully');
        } else {
            // Collection déjà existante, pas besoin de créer une nouvelle collection
            return new Response('Collection already exists');
        }
    
    }

    public function updateVehicle(string $id, array $vehicleDatas) : JsonResponse {
        $vehicle = $this->dm->getRepository(Vehicle::class)->find($id);
        if (!$vehicle) {
            throw new \InvalidArgumentException('Vehicle not found for ID ' . $id);
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

        $serializeVehicle = $this->serializer->serialize($vehicle, 'json');

        // $serializeVehicle = $this->serializer->serialize($vehicle, 'json');
        return new JsonResponse($serializeVehicle, 200, [], true);
    }

    public function deleteVehicle(string $id):Response { 
        $vehicle = $this->dm->getRepository(Vehicle::class)->find($id);
        if(!$vehicle){
            throw new \InvalidArgumentException('Vehicle not found for ID :' . $id);
        }
        $this->dm->remove($vehicle);
        $this->dm->flush();

        return new Response('operation succed, the vehicle ' . $id . 'has been deleted');
    }

    public function getVehicle($plateNumber) : JsonResponse {
        if(!$plateNumber){
            throw new \InvalidArgumentException('oups something went wrong with your platenumber : ' . $plateNumber);
        }
        $vehicle = $this->dm->getRepository(Vehicle::class)->findOneBy(['plateNumber' => $plateNumber]);
        if(!$vehicle){
            throw new \InvalidArgumentException('no vehicle found with plateNumber : ' . $plateNumber);
        }
        $serializeVehicle = $this->serializer->serialize($vehicle, 'json');
        return new JsonResponse($serializeVehicle, 200, [], true);
    }

    public function countVehicle($km) : JsonResponse {
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
        $serializeVehicles = $this->serializer->serialize($vehicles, 'json');
        
        return new JsonResponse($serializeVehicles, 200, [], true);
    }
    
}