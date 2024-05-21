<?php

namespace App\Controller;

use App\Service\Security\CustomHttpClient;
use App\Service\Vehicle\VehicleService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class VehicleController extends AbstractController
{
    private $vehicleService;
    private $customHttpClient;
    private $authorizationChecker;
    
    private $serializer;

    public function __construct(
        VehicleService $vehicleService, 
        CustomHttpClient $customHttpClient, 
        AuthorizationCheckerInterface $authorizationChecker, 
        SerializerInterface $serializer
    ) {
        // initialise le constructeur avec ses dépendances
        // l'objet service de l'entité vehicle
        // le serializer 
        $this->vehicleService = $vehicleService;
        $this->customHttpClient = $customHttpClient;
        $this->authorizationChecker = $authorizationChecker;
        $this->serializer = $serializer;
    }

    
    #[Route('/api/vehicle', name: 'create_vehicle', methods: ['POST'])]
    public function createVehicle(Request $request) : JsonResponse
    {
        // convertir le contenu de la requête en valeur de type php qui sera manipulée par le service
        $requestDatas = json_decode($request->getContent(), true);
        // injecte SERVICE createVehicle avec le contenu de la requête en PROP
        // retourne une réponse au format php
        $vehicle = $this->vehicleService->createVehicle($requestDatas);
        // converti au format JSON la réponse du service 
        $serializeResponse = $this->serializer->serialize($vehicle, 'json');
        // retourne une réponse de type nouvelle réponse JSON
        // true indique que la donnée est au format JSON
        return new JsonResponse($serializeResponse, Response::HTTP_OK, [], true);
    }

    #[Route('/api/vehicle/create-table', name: 'create_table', methods: ['POST'])]
    public function createTable() {
        // créer une table SI ET SEULEMENT SI elle n'existe pas DEJA
        return $this->vehicleService->createCollection();
    }
     
    #[Route('/api/vehicle/{vehicleId}', name: 'update_vehicle', methods: ['PUT'])]
    public function updateVehicle(Request $request, $vehicleId): JsonResponse {
        // converti au format php le contenu de la requête
        // passe le param dynamique de l'url (vehicleId) en tant que prop de la méthode
        $requestDatas = json_decode($request->getContent(), true);
        // injecte SERVICE updateVehicle avec 2 valeurs en prop : l'id du vehicule, le contenu de la requête
        // service retourne une réponse au format PHP
        $vehicle = $this->vehicleService->updateVehicle($vehicleId, $requestDatas);
        // converti la réponse au format json
        $serializeVehicle = $this->serializer->serialize($vehicle, 'json');
        // retourne une nouvelle réponse JSON 
        // true indique que la donnée est au format JSON
        return new JsonResponse($serializeVehicle, Response::HTTP_OK, [], true);
    }

    #[Route('/api/vehicle/{vehicleId}', name: "delete_vehicle", methods: ['DELETE'])]
    public function deleteVehicle($vehicleId) : JsonResponse
    {
        // passe l'id vehicule en prop de la méthode
        // obtient l'id véhicule à partir de l'url où il est un param dynamique
        // injecte SERVICE deleteVehicle qui prend l'id vehicule en prop
        $response = $this->vehicleService->deleteVehicle($vehicleId);
        // retourne réponse de type nouvelel réponse json
        return new JsonResponse($response, Response::HTTP_OK, []);
    }

    #[Route('/api/vehicle/{plateNumber}', name: 'get_vehicle', methods: ['GET'])]
    public function getVehicle($plateNumber): JsonResponse
    // passe la propriété plateNumber en tant que prop de la méthode
    // obtient la prop plateNumber à partir de l'url où elle est un param dynamique
    {
        // injecte service getVehicle 
        // service prend en prop plateNumber
        // service retourne de l'objet PHP
        $vehicle = $this->vehicleService->getVehicle($plateNumber);
        // converti l'objet retourné par le service en tant qu'objet JSON
        $serializeVehicle = $this->serializer->serialize($vehicle, 'json');
        // retourne une réponse de type nouvelle réponse au format JSON
        // true indique que la donnée est au format JSON
        return new JsonResponse($serializeVehicle, Response::HTTP_OK, [], true);
    }

    #[Route('/api/vehicle/{km}/total', name: 'count_vehicle_by_km', methods: ['GET'])]
    public function countVehicleByKm($km): JsonResponse
    {
        // méthode prend une prop km 
        // prop obtenu à partir de l'url où elle est un param dynamique
        // injecte SERVICE countVehicule qui prend la prop km en prop
        $vehicles = $this->vehicleService->countVehicle($km);
        // converti la réponse du service en objet json
        $serializeVehicle = $this->serializer->serialize($vehicles, 'json');
        // retourne une réponse de type nouvelle réponse Json
        // true indique que la donnée est au format json
        return new JsonResponse($serializeVehicle, Response::HTTP_OK, [], true);
    }


    #[Route('/api/vehicle/{vehicleId}/contracts', name: 'get_contracts_from_vehicle', methods: ['GET'])]
    public function getContractsFromVehicleId($vehicleId): JsonResponse
    {
        // méthode prend 1 valeur en prop, l'id vehicle
        // cette valeur est obtenue à partir de l'url où elle est un param dynamique
        // injecte SERVICE qui prend l'id en tant que prop
        // service retourne un objet php
        $contracts = $this->vehicleService->getContractsFromVehicleId($vehicleId);
        // converti l'objet php en json
        $serializeContract = $this->serializer->serialize($contracts, 'json', ['groups' => ['contract', 'billing']]);
        // retourne une réponse de type nouvelle réponse json
        // true indique que la donnée est au format JSON
        return new JsonResponse($serializeContract, Response::HTTP_OK, [],  true);
    }

    #[Route('/api/vehicle/contracts/late/on-average', name: 'get_late_time_on_average_per_vehicle', methods: ['GET'])]
    public function getLateTimeOnAverageByVehicle(): JsonResponse
    {
        // injecte service qui retourne un objet php
        $vehicles = $this->vehicleService->getLateTimeOnAverageByVehicle();
        // converti l'objet php en json
        // utilise les group pour éviter le probleme de référence circulaire
        $serializeContract = $this->serializer->serialize($vehicles, 'json', ['groups' => ['contract', 'billing']]);
        // retourne réponse de type nouvelle réponse json
        // true indique que la donnée est de type php
        return new JsonResponse($serializeContract, Response::HTTP_OK, [],  true);
    }

    #[Route('/api/vehicle/contracts', name: 'get_contracts_groupby_vehicle', methods: ['GET'])]
    public function getContractsGroupByVehicle(): JsonResponse
    {
    // injecte service qui retourne un objet php
       $contractsByVehicle = $this->vehicleService->getContractsGroupByVehicle();
       // converti l'objet php en json
       $serializeResponse = $this->serializer->serialize($contractsByVehicle, 'json', ['groups' => ['contract', 'billing']]);
       // retourne réponse de type nouvelle réponse json 
       // true indique que la donnée est au foramt JSON
       return new JsonResponse($serializeResponse, Response::HTTP_OK, [], true);
    }

}
