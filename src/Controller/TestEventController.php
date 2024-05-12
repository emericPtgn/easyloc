<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TestEventController extends AbstractController
{
    #[Route('/test-exception', name: 'test_exception')]
    public function testException(): Response
    {
        // Lancer une exception pour tester l'écouteur d'événement
        throw new \Exception('Ceci est un test d\'exception !', 500);
    }

}
