<?php

namespace App\Document;
use App\Entity\Contract;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document]

class Vehicle {

    #[MongoDB\Id]
        /**
     * @SerializedName("id")
     */
    protected string $id;

    #[MongoDB\Field(type: 'string', name: 'plateNumber')]
    /**
     * @SerializedName("plateNumber")
     */
    protected string $plateNumber = '' ;

    #[MongoDB\Field(type: 'string', name: 'informations')]
        /**
     * @SerializedName("informations")
     */
    protected string $informations = '';
    #[MongoDB\Field(type: 'int', name: 'km')]
            /**
     * @SerializedName("km")
     */
    protected int $km = 0;

    /**
     * @var Collection<int, Contract>
     */
    #[ORM\OneToMany(targetEntity: Contract::class, mappedBy: 'vehicle')]
    private Collection $contracts;

    public function __construct()
    {
        $this->contracts = new ArrayCollection();
        $this->plateNumber = '';
        $this->informations = '';
        $this->km = 0;
    }

    public function __toString() {
        return $this->id;
    }

    public function getId(){
        return $this->id;
    }
    
    public function setId($id) : static {
        $this->id = $id; 
        return $this;
    }
    
    public function getPlateNumber(){
        return $this->plateNumber;
    }
    
    public function setPlateNumber($plateNumber){
        $this->plateNumber = $plateNumber;
    }
    public function getInformations(){
        return $this->informations;
    }
    public function setInformations($informations){
        $this->informations = $informations;
    }
    public function getKm(){
        return $this->km;
    }
    public function setKm($km){
        $this->km = $km;
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
            $contract->setVehicle($this);
        }

        return $this;
    }

    public function removeContract(Contract $contract): static
    {
        if ($this->contracts->removeElement($contract)) {
            // set the owning side to null (unless already changed)
            if ($contract->getVehicle() === $this) {
                $contract->setVehicle(null);
            }
        }

        return $this;
    }

}