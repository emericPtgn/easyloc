<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class VehicleController extends AbstractController
{
    #[Route('/vehicle', name: 'app_vehicle')]
    public function index(): Response
    {
        return $this->render('vehicle/index.html.twig', [
            'controller_name' => 'VehicleController',
        ]);
    }
}
