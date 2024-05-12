<?php

namespace App\Document; // Utilisez le bon namespace correspondant au chemin du fichier

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document]
class Product
{
    #[MongoDB\Id(strategy:"AUTO")]
    private $id;

    #[MongoDB\Field(type: 'string', name: 'title')]
    private $title;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
}
