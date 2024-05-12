<?php

namespace App\Entity;
use App\Document\Product;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\CommandeRepository;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
#[ORM\Table(name: "commande")]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: "productId", type: "string", length: 255)]
    private $productId;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }
    

    public function setProduct(Product $product): void
    {
        $this->productId = $product->getId();
        $this->product = $product;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }
}