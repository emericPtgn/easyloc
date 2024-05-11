<?php

namespace App\Document;

use App\Entity\Contract;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document]
class Customer
{
    #[MongoDB\Id(strategy:"AUTO")]
    protected string $id;

    #[MongoDB\Field(type: 'string', name: 'firstName')]
    protected string $firstName;

    #[MongoDB\Field(type: 'string', name: 'lastName')]
    protected string $lastName;

    #[MongoDB\Field(type: 'string', name: 'adress')]
    protected string $adress;

    #[MongoDB\Field(type: 'string', name: 'permitNumber')]
    protected string $permitNumber;

    /**
     * @var Collection<int, Contract>
     */
    #[ORM\OneToMany(targetEntity: Contract::class, mappedBy: 'customer')]
    private Collection $contracts;

    public function __construct()
    {
        $this->contracts = new ArrayCollection();
    }

    
    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }


    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getAdress(): string
    {
        return $this->adress;
    }

    public function setAdress(string $adress): void
    {
        $this->adress = $adress;
    }

    public function getPermitNumber(): string
    {
        return $this->permitNumber;
    }

    public function setPermitNumber(string $permitNumber): void
    {
        $this->permitNumber = $permitNumber;
    }

    /**
     * @return Collection<int, Contract>
     */
    public function getContracts(): Collection
    {
        return $this->contracts;
    }

    public function addContract(Contract $contract): static
    {
        if (!$this->contracts->contains($contract)) {
            $this->contracts->add($contract);
            $contract->setCustomer($this);
        }

        return $this;
    }

    public function removeContract(Contract $contract): static
    {
        if ($this->contracts->removeElement($contract)) {
            // set the owning side to null (unless already changed)
            if ($contract->getCustomer() === $this) {
                $contract->setCustomer(null);
            }
        }

        return $this;
    }
}
