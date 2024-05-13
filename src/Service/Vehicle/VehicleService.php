<?php

namespace App\Service\Vehicle;
use Exception;
use App\Document\Vehicle;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface;


class VehicleService {
    private $dm;
    private $serializer;
    
    public function __construct(DocumentManager $dm, SerializerInterface $serializer){
        $this->dm = $dm;
        $this->serializer = $serializer;
    }

    public function createVehicle(array $vehicleDatas) : Vehicle {
        $vehicle = new Vehicle();
        if(isset($vehicleDatas['informations'])){
            $vehicle->setInformations($vehicleDatas['informations']);
        }
        if(isset($vehicleDatas['km'])){
            $vehicle->setKm($vehicleDatas['km']);
        }
        if(isset($vehicleDatas['plateNumber'])){
            $vehicle->setPlateNumber($vehicleDatas['plateNumber']);
        }
        $this->dm->persist($vehicle);
        $this->dm->flush();

        return $vehicle;
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
        // Vérifiez si la valeur du paramètre est numérique
        if (!is_numeric($km)) {
            throw new \InvalidArgumentException('The filter value must be numeric.');
        }
        // Convertissez la valeur en entier
        $filterValue = (int)$km;
        
        // Utilisez la valeur filtrée pour rechercher les véhicules
        $qb = $this->dm->createQueryBuilder(Vehicle::class)
        ->field('km')->gte($km);
        $query = $qb->getQuery();
        $vehicles = $query->execute();
        $serializeVehicles = $this->serializer->serialize($vehicles, 'json');
        
        return new JsonResponse($serializeVehicles, 200, [], true);
    }
    
}