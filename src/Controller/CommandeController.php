<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Document\Product;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CommandeController extends AbstractController
{

    /**
     * @Route("/test_commande", name="test_commande")
     */
    public function testCommande(DocumentManager $dm, EntityManagerInterface $em, LoggerInterface $logger): Response
    {
        $product = new Product();
        $product->setTitle('blue mug');
        $dm->persist($product);
        $dm->flush();

        $commande = new Commande();
        $commande->setProductId($product->getId());
        $commande->setProduct($product);
        $em->persist($commande);
        $em->flush();
        $logger->info('this is command. id : '.$commande->getId());
        $logger->info($commande->getProductId());

        $commande = $em->find(Commande::class, $commande->getId()); 
        $product = $commande->getProduct();
        $title = $product->getTitle();

        // Retourner une réponse
        return new JsonResponse($title, 200, [], false);
    }
}
