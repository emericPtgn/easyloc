<?php

namespace App\Entity;

use App\Repository\ContractRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;


#[ORM\Entity(repositoryClass: ContractRepository::class)]
class Contract
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id", type: "integer")]
    #[Groups(['contract'])]
    private ?int $id = null;

    #[ORM\Column(name: "customerId", type: "string", length: 255)]
    #[Groups(['contract'])]
    private ?string $customerId = null;

    #[ORM\Column(name: "vehicleId", type: "string", length: 255)]
    #[Groups(['contract'])]
    private ?string $vehicleId = null;

    #[ORM\Column(name: "signDateTime", type: Types::DATETIME_MUTABLE)]
    #[Groups(['contract'])]
    private ?\DateTimeInterface $signDateTime = null;

    #[ORM\Column(name: "locBeginDateTime", type: Types::DATETIME_MUTABLE)]
    #[Groups(['contract'])]
    private ?\DateTimeInterface $locBeginDateTime = null;

    #[ORM\Column(name: "locEndDateTime", type: Types::DATETIME_MUTABLE)]
    #[Groups(['contract'])]
    private ?\DateTimeInterface $locEndDateTime = null;

    #[ORM\Column(name: "returningDateTime", type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['contract'])]
    private ?\DateTimeInterface $returningDateTime = null;

    #[ORM\Column(name: "price", type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['contract'])]
    private ?string $price = null;

    /**
     * @var Collection<int, Billing>
     */
    #[ORM\OneToMany(targetEntity: Billing::class, mappedBy: 'contract')]
    #[MaxDepth(1)] // Limite la profondeur de sérialisation à 1
    #[Groups(['contract'])]
    private Collection $billings;

    public function __construct()
    {
        $this->billings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    public function setCustomerId(string $customerId): static
    {
        $this->customerId = $customerId;

        return $this;
    }

    public function getVehicleId(): ?string
    {
        return $this->vehicleId;
    }

    public function setVehicleId(string $vehicleId): static
    {
        $this->vehicleId = $vehicleId;

        return $this;
    }

    public function getSignDateTime(): ?\DateTimeInterface
    {
        return $this->signDateTime;
    }

    public function setSignDateTime(\DateTimeInterface $signDateTime): static
    {
        $this->signDateTime = $signDateTime;

        return $this;
    }

    public function getLocBeginDateTime(): ?\DateTimeInterface
    {
        return $this->locBeginDateTime;
    }

    public function setLocBeginDateTime(\DateTimeInterface $locBeginDateTime): static
    {
        $this->locBeginDateTime = $locBeginDateTime;

        return $this;
    }

    public function getLocEndDateTime(): ?\DateTimeInterface
    {
        return $this->locEndDateTime;
    }

    public function setLocEndDateTime(\DateTimeInterface $locEndDateTime): static
    {
        $this->locEndDateTime = $locEndDateTime;

        return $this;
    }

    public function getReturningDateTime(): ?\DateTimeInterface
    {
        return $this->returningDateTime;
    }

    public function setReturningDateTime(?\DateTimeInterface $returningDateTime): static
    {
        $this->returningDateTime = $returningDateTime;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return Collection<int, Billing>
     */
    public function getBillings(): Collection
    {
        return $this->billings;
    }

    public function addBilling(Billing $billing): static
    {
        if (!$this->billings->contains($billing)) {
            $this->billings->add($billing);
            $billing->setContract($this);
        }

        return $this;
    }

    public function removeBilling(Billing $billing): static
    {
        if ($this->billings->removeElement($billing)) {
            // set the owning side to null (unless already changed)
            if ($billing->getContract() === $this) {
                $billing->setContract(null);
            }
        }

        return $this;
    }
}
