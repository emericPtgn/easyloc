<?php

namespace App\DataFixtures;

use App\Entity\Billing;
use App\Entity\Contract;
use App\Document\Vehicle;
use App\Document\Customer;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\ODM\MongoDB\DocumentManager;

class SqlFixtures extends Fixture implements FixtureGroupInterface
{
    private DocumentManager $mongoDM;
    private ?string $contractId = null;
    private ?string $billingId = null;


    public function __construct(DocumentManager $mongoDM)
    {
        $this->mongoDM = $mongoDM;
    }

    public function load(ObjectManager $manager): void
    {
        // Use $this->mongoDM to interact with MongoDB
        $customer = $this->mongoDM->getRepository(Customer::class)->findOneBy(['lastName' => 'montaigu']);
        $idCustomer = $customer->getId();
        $customer2 = $this->mongoDM->getRepository(Customer::class)->findOneBy(['lastName' => 'zaziot']);
        $idCustomer2 = $customer2->getId();
        $vehicle = $this->mongoDM->getRepository(Vehicle::class)->findOneBy(['plateNumber' => 'rre007']);
        $idVehicle = $vehicle->getId();
        $vehicle2 = $this->mongoDM->getRepository(Vehicle::class)->findOneBy(['plateNumber' => 'lkk789']);
        $idVehicle2 = $vehicle2->getId();

        // Create contracts in SQL
        $contract = new Contract();
        $contract->setId(1);
        $contract->setCustomerId($idCustomer);
        $contract->setVehicleId($idVehicle);
        $signDateTime = new \DateTime('2024-05-20 12:00:00');
        $locBeginDateTime = new \DateTime('2024-05-20 12:00:00');
        $locEndDateTime = new \DateTime('2024-05-20 17:00:00');
        $returningDateTime = new \DateTime('2024-05-20 19:00:00');
        $contract->setReturningDateTime($returningDateTime);
        $contract->setLocBeginDateTime($locBeginDateTime);
        $contract->setLocEndDateTime($locEndDateTime);
        $contract->setSignDateTime($signDateTime);
        $contract->setPrice(50);
        $manager->persist($contract);
        $manager->flush();
        $this->contractId = $contract->getId();

        $contract2 = new Contract();
        $contract2->setId(2);
        $contract2->setCustomerId($idCustomer2);
        $contract2->setVehicleId($idVehicle2);
        $signDateTime = new \DateTime('2024-05-18 12:00:00');
        $locBeginDateTime = new \DateTime('2024-05-18 13:00:00');
        $locEndDateTime = new \DateTime('2024-05-22 08:00:00');
        $returningDateTime = new \DateTime('2024-05-22 08:30:00');
        $contract2->setReturningDateTime($returningDateTime);
        $contract2->setLocBeginDateTime($locBeginDateTime);
        $contract2->setLocEndDateTime($locEndDateTime);
        $contract2->setSignDateTime($signDateTime);
        $contract2->setPrice(450);
        $manager->persist($contract2);
        $manager->flush();

        $contract3 = new Contract();
        $contract3->setId(3);
        $contract3->setCustomerId($idCustomer2);
        $contract3->setVehicleId($idVehicle);
        $signDateTime = new \DateTime('2024-05-21 12:00:00');
        $locBeginDateTime = new \DateTime('2024-05-22 08:00:00');
        $locEndDateTime = new \DateTime('2024-05-22 12:00:00');
        $contract3->setReturningDateTime(null);
        $contract3->setLocBeginDateTime($locBeginDateTime);
        $contract3->setLocEndDateTime($locEndDateTime);
        $contract3->setSignDateTime($signDateTime);
        $contract3->setPrice(50);
        $manager->persist($contract3);
        $manager->flush();

        $contract4 = new Contract();
        $contract4->setId(4);
        $contract4->setCustomerId($idCustomer);
        $contract4->setVehicleId($idVehicle2);
        $signDateTime = new \DateTime('2024-05-18 12:00:00');
        $locBeginDateTime = new \DateTime('2024-05-18 13:00:00');
        $locEndDateTime = new \DateTime('2024-05-22 08:00:00');
        $returningDateTime = new \DateTime('2024-05-22 10:00:00');
        $contract4->setReturningDateTime($returningDateTime);
        $contract4->setLocBeginDateTime($locBeginDateTime);
        $contract4->setLocEndDateTime($locEndDateTime);
        $contract4->setSignDateTime($signDateTime);
        $contract4->setPrice(450);
        $manager->persist($contract4);
        $manager->flush();

        // Create billings in SQL
        $billing = new Billing();
        $billing->setId(1);
        $billing->setAmount(50);
        $billing->setContract($contract);
        $manager->persist($billing);
        $manager->flush();
        $this->billingId = $billing->getId();

        $billing2 = new Billing();
        $billing2->setId(2);
        $billing2->setAmount(300);
        $billing2->setContract($contract2);
        $manager->persist($billing2);
        $manager->flush();

        $billing3 = new Billing();
        $billing3->setId(3);
        $billing3->setAmount(50);
        $billing3->setContract($contract3);
        $manager->persist($billing3);
        $manager->flush();
    }

    public function setContractId(string $contractId): void
    {
        $this->contractId = $contractId;
    }

    public function getContractId(): ?string
    {
        return $this->contractId;
    }
    public function setBillingId(string $billingId): void
    {
        $this->billingId = $billingId;
    }

    public function getBillingId(): ?string
    {
        return $this->billingId;
    }



    /**
     * This method must return an array of groups
     * on which the implementing class belongs to
     * @return string[]
     */

    public static function getGroups(): array
    {
        return ['test'];
    }
}
