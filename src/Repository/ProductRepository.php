<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findProductsByOrderId($orderId)
    {
        // Implémentez la logique pour récupérer les produits associés à une commande
        // Utilisez les méthodes de votre EntityManager ou QueryBuilder pour effectuer la requête
    }
}