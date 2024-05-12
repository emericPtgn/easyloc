<?php

namespace App\Service\Commande;
use App\Document\Product;
use App\Entity\Commande;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class CommandeService {

    private $dm;
    private $em;

    public function __construct(DocumentManager $dm, EntityManagerInterface $em){
        $this->em = $em;
        $this->dm = $dm;
    }   

    public function newProduct(): Product
    {
        $product = new Product();
        $product->setTitle('red mug');
        $this->dm->persist($product);
        $this->dm->flush();
        return $product;
    }

    public function newCommande(Product $product): Commande
    {
        $commande = new Commande();
        $commande->setProductId($product->getId());
        $commande->setProduct($product);
        $this->em->persist($commande);
        $this->em->flush();
        return $commande;
    }

    public function testMethod(Commande $commande): string
    {
        $commande = $this->em->find(Commande::class, $commande->getId()); 
        $product = $commande->getProduct();
        return "Order Title: " . $product->getTitle();
    }
}
