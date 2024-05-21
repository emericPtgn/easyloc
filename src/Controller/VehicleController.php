<?php

namespace App\Controller;

use App\Service\Contract\ContractService;
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
        VehicleService $vehicleService, // Injectez le service VehicleService
        CustomHttpClient $customHttpClient, // Injectez le service CustomHttpClient
        AuthorizationCheckerInterface $authorizationChecker, // Injectez le service AuthorizationCheckerInterface
        SerializerInterface $serializer
    ) {
        $this->vehicleService = $vehicleService;
        $this->customHttpClient = $customHttpClient;
        $this->authorizationChecker = $authorizationChecker;
        $this->serializer = $serializer;
    }


    #[Route('/api/vehicle', name: 'create_vehicle', methods: ['POST'])]
    public function createVehicle(Request $request) : JsonResponse
    {
        $requestDatas = json_decode($request->getContent(), true);
        $vehicle = $this->vehicleService->createVehicle($requestDatas);
        $serializeResponse = $this->serializer->serialize($vehicle, 'json');
        return new JsonResponse($serializeResponse, Response::HTTP_OK, [], true);
    }

    #[Route('/api/vehicle/create-table', name: 'create_table', methods: ['POST'])]
    public function createTable() {
        return $this->vehicleService->createCollection();
    }

    #[Route('/api/vehicle/{vehicleId}', name: 'update_vehicle', methods: ['PUT'])]
    public function updateVehicle(Request $request, $vehicleId): JsonResponse {
        $requestDatas = json_decode($request->getContent(), true);
        $vehicle = $this->vehicleService->updateVehicle($vehicleId, $requestDatas);
        $serializeVehicle = $this->serializer->serialize($vehicle, 'json');
        return new JsonResponse($serializeVehicle, Response::HTTP_OK, [], true);
    }

    #[Route('/api/vehicle/{vehicleId}', name: "delete_vehicle", methods: ['DELETE'])]
    public function deleteVehicle($vehicleId) : JsonResponse
    {
        $response = $this->vehicleService->deleteVehicle($vehicleId);
        if(!$response){
            throw new \ErrorException("oups something went wrong !");
        }
        return new JsonResponse($response, Response::HTTP_OK, []);
    }

    #[Route('/api/vehicle/{plateNumber}', name: 'get_vehicle', methods: ['GET'])]
    public function getVehicle($plateNumber): JsonResponse
    {
        $vehicle = $this->vehicleService->getVehicle($plateNumber);
        $serializeVehicle = $this->serializer->serialize($vehicle, 'json');
        return new JsonResponse($serializeVehicle, Response::HTTP_OK, [], true);
    }

    #[Route('/api/vehicle/{km}/total', name: 'count_vehicle_by_km', methods: ['GET'])]
    public function countVehicleByKm($km): JsonResponse
    {
        $vehicles = $this->vehicleService->countVehicle($km);
        $serializeVehicle = $this->serializer->serialize($vehicles, 'json');
        return new JsonResponse($serializeVehicle, Response::HTTP_OK, [], true);
    }


    #[Route('/api/vehicle/{vehicleId}/contracts', name: 'get_contracts_from_vehicle', methods: ['GET'])]
    public function getContractsFromVehicleId($vehicleId): JsonResponse
    {
        $contracts = $this->vehicleService->getContractsFromVehicleId($vehicleId);
        $serializeContract = $this->serializer->serialize($contracts, 'json', ['groups' => ['contract', 'billing']]);
        return new JsonResponse($serializeContract, Response::HTTP_OK, [],  true);
    }

    #[Route('/api/vehicle/contracts/late/on-average', name: 'get_late_time_on_average_per_vehicle', methods: ['GET'])]
    public function getLateTimeOnAverageByVehicle(): JsonResponse
    {
        $vehicles = $this->vehicleService->getLateTimeOnAverageByVehicle();
        $serializeContract = $this->serializer->serialize($vehicles, 'json', ['groups' => ['contract', 'billing']]);
        return new JsonResponse($serializeContract, Response::HTTP_OK, [],  true);
    }

    #[Route('/api/vehicle/contracts', name: 'get_contracts_groupby_vehicle', methods: ['GET'])]
    public function getContractsGroupByVehicle(): JsonResponse
    {
       $contractsByVehicle = $this->vehicleService->getContractsGroupByVehicle();
       $serializeResponse = $this->serializer->serialize($contractsByVehicle, 'json', ['groups' => ['contract', 'billing']]);
       return new JsonResponse($serializeResponse, Response::HTTP_OK, [], true);
    }



}
