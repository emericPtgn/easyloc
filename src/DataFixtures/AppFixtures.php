<?php

namespace App\DataFixtures;

use App\Entity\Billing;
use App\Entity\Contract;
use App\Document\Vehicle;
use App\Document\Customer;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\MongoDBBundle\Fixture\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

class AppFixtures extends Fixture implements FixtureGroupInterface
{
    private ?string $customerId = null;
    private ?string $vehicleId = null;

    public function load(ObjectManager $manager): void
    {
        // Create clients
        $customer = new Customer();
        $customer->setId('664dd118ac4ff014ff080396');
        $customer->setAdress('1 rue du pleutre bruxelles 1000');
        $customer->setFirstName('patrick');
        $customer->setLastName('montaigu');
        $customer->setPermitNumber('123002');
        $manager->persist($customer);
        $manager->flush();
        $this->customerId = $customer->getId();

        $customer2 = new Customer();
        $customer2->setId('664dd118ac4ff014ff080397');
        $customer2->setAdress('820B avenue du président 75000 paris');
        $customer2->setFirstName('zackaria');
        $customer2->setLastName('zaziot');
        $customer2->setPermitNumber('844622');
        $manager->persist($customer2);
        $manager->flush();

        $vehicle = new Vehicle();
        $vehicle->setId('664dd594b23d381f0f2933e4');
        $vehicle->setInformations('excellent état');
        $vehicle->setKm(1000);
        $vehicle->setPlateNumber("rre007");
        $manager->persist($vehicle);
        $manager->flush();
        $this->vehicleId = $vehicle->getId();

        $vehicle2 = new Vehicle();
        $vehicle2->setId('664dd5cfb23d381f0f2933e5');
        $vehicle2->setInformations('mauvais état');
        $vehicle2->setKm(300000);
        $vehicle2->setPlateNumber("lkk789");
        $manager->persist($vehicle2);
        $manager->flush();



    }

    public function setCustomerId(string $customerId): void
    {
        $this->customerId = $customerId;
    }

    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }
    public function setVehicleId(string $vehicleId): void
    {
        $this->vehicleId = $vehicleId;
    }

    public function getVehicleId(): ?string
    {
        return $this->vehicleId;
    }

    /**
     * This method must return an array of groups
     * on which the implementing class belongs to
     * @return string[]
     */
    public static function getGroups(): array {
        return ['test'];
    }
}
