<?php

namespace App\Controller;

use App\Service\Vehicle\VehicleService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class VehicleController extends AbstractController
{
    private $vehicleService;

    public function __construct(VehicleService $vehicleService) {
        $this->vehicleService = $vehicleService;
    }

    #[Route('/api/vehicle', name: 'create_vehicle', methods: ['POST'])]
    public function createVehicle(Request $request) {
        if($request->query->get('action')  === 'create-collection'){
            return $this->vehicleService->createCollection($request);
        } return $this->vehicleService->createVehicle($request);
    }

    #[Route('/api/vehicle', name: 'update_vehicle', methods: ['PUT'])]
    public function updateVehicle(Request $request): JsonResponse {
        $id = $request->query->get('id');
        $requestDatas = json_decode($request->getContent(), true);
        $vehicle = $this->vehicleService->updateVehicle($id, $requestDatas);
        return new JsonResponse($vehicle, 200, [], true);
    }

    #[Route('/api/vehicle', name: "delete_vehicle", methods: ['DELETE'])]
    public function deleteVehicle(Request $request): Response {
        $id = $request->query->get('id');
        if(!$id){
            throw new \InvalidArgumentException('oups, something went wrong : invalid ID');
        }
        $response = $this->vehicleService->deleteVehicle($id);
        if(!$response){
            throw new \ErrorException("oups something went wrong !");
        }
        return new Response ($response);
    }

    #[Route('/api/vehicle', name: 'get_vehicle', methods: ['GET'])]
    public function getVehicle(Request $request) : JsonResponse {
        $plateNumber = $request->query->get('plateNumber');
        $km = $request->query->get('km');
        if($plateNumber){
            $response = $this->vehicleService->getVehicle($plateNumber);
        } elseif($km){
            $response = $this->vehicleService->countVehicle($km);
        } else {
            throw new \InvalidArgumentException('oups something went wrong with your request');
        }
        return new JsonResponse($response, 200, [], true);
    }
}
