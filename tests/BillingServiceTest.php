<?php

namespace App\Tests;

use DateTime;
use App\Document\Vehicle;
use App\Document\Customer;
use App\DataFixtures\AppFixtures;
use App\DataFixtures\SqlFixtures;
use App\Repository\BillingRepository;
use App\Repository\VehicleRepository;
use App\Repository\ContractRepository;
use App\Repository\CustomerRepository;
use App\Service\Billing\BillingService;
use App\Service\Vehicle\VehicleService;
use Doctrine\Common\DataFixtures\Loader;
use App\Service\Contract\ContractService;
use App\Service\Customer\CustomerService;
use Doctrine\ODM\MongoDB\DocumentManager;
use phpDocumentor\Reflection\Types\Void_;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\MongoDBPurger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\Common\DataFixtures\Executor\MongoDBExecutor;


class BillingServiceTest extends KernelTestCase
{
    private BillingService $billingService;
    private ContractService $contractService;

    private VehicleService $vehicleService;
    private CustomerService $customerService;
    private DocumentManager $documentManager;
    private ?string $contractId = null; 
    private ?string $billingId = null; 
    private ?string $customerId = null;
    private ?string $vehicleId = null;
    private BillingRepository $billingRepository;
    private ContractRepository $contractRepository;
    private VehicleRepository $vehicleRepository;
    private CustomerRepository $customerRepository;
    

    public static function setUpBeforeClass(): void
    {
        // Initialize any class-level setup if needed
    }

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->billingService = static::getContainer()->get(BillingService::class);
        $this->contractService = static::getContainer()->get(ContractService::class);
        $this->vehicleService = static::getContainer()->get(VehicleService::class);
        $this->customerService = static::getContainer()->get(CustomerService::class);
        $this->documentManager = static::getContainer()->get('doctrine_mongodb')->getManager();
        $this->billingRepository = static::getContainer()->get(BillingRepository::class);
        $this->contractRepository = static::getContainer()->get(ContractRepository::class);
        $this->vehicleRepository = static::getContainer()->get(VehicleRepository::class);
        $this->customerRepository = static::getContainer()->get(CustomerRepository::class);

        // Purge MongoDB collections using custom purger
        $mongoPurger = new MongoDBPurger($this->documentManager);
        $mongoPurger->purge();

        // Execute MongoDB fixtures
        $mongoExecutor = new MongoDBExecutor($this->documentManager, $mongoPurger);
        $mongoLoader = new Loader();
        $appFixtures = new AppFixtures();
        $mongoLoader->addFixture($appFixtures);
        $mongoExecutor->execute($mongoLoader->getFixtures());

        // Execute SQL Server fixtures
        $em = static::getContainer()->get('doctrine')->getManager();
        $purger = new ORMPurger($em);
        $executor = new ORMExecutor($em, $purger);
        $loader = new Loader();
        $sqlFixtures = new SqlFixtures($this->documentManager);
        $loader->addFixture($sqlFixtures);
        $executor->execute($loader->getFixtures());

        // Récupérer le contractId après l'exécution des fixtures
        $this->contractId = $sqlFixtures->getContractId();
        $this->billingId = $sqlFixtures->getBillingId();
        $this->customerId = $appFixtures->getCustomerId();
        $this->vehicleId = $appFixtures->getVehicleId();
    }

// -------------------- TEST BILLING SERVICE ----------------------- //


    public function testCreateBilling(): void
    {
        $billling = $this->billingService->createBilling($this->contractId, 20);
        $this->assertEquals(20, $billling->getAmount());
    }

    public function testUpdateBilling(): void
    {
        $billing = $this->billingService->updateBilling($this->billingId, 50);
        $this->assertEquals(50, $billing->getAmount());
    }

    public function testGetBilling(): void
    {
        $billing = $this->billingService->getBilling($this->billingId);
        $this->assertNotNull($billing);
        $this->assertEquals(50, $billing->getAmount());
    }

    public function testDeleteBilling(): void
    {
        $this->billingService->deleteBilling($this->billingId);

        // Vérification dans la base de données
        $deletedBilling = $this->billingRepository->find($this->billingId);
        $this->assertNull($deletedBilling, 'Le billing n\'a pas été supprimé de la base de données.');

/*         // Vérification via l'API
        $response = $this->apiClient->get('/billing/' . $this->billingId);
        $this->assertEquals(404, $response->getStatusCode(), 'Le billing est toujours accessible via l\'API.'); */
    }


// -------------------- TEST CONTRACT SERVICE ----------------------- //


    public function testCreateContract(): void
    {
        $contractData = [];
        $contractData['signDateTime'] = "2024-05-20 12:00:00";
        $contractData['locBeginDateTime'] = "2024-05-20 12:00:00";
        $contractData['locEndDateTime'] = "2024-05-20 17:00:00";
        $contractData['returningDateTime'] = "2024-05-20 18:30:00";
        $contractData['price'] = 70;

        $this->contractService->createContract('664dd594b23d381f0f2933e4', '664dd118ac4ff014ff080396', $contractData);

        // Vérification dans la base de données
        $createdContract = $this->contractRepository->findBy([
            'vehicleId' => '664dd594b23d381f0f2933e4',
            'customerId' => '664dd118ac4ff014ff080396',
            'signDateTime' => new DateTime($contractData['signDateTime']),
            'locBeginDateTime' => new DateTime($contractData['locBeginDateTime']),
            'locEndDateTime' => new DateTime($contractData['locEndDateTime']),
            'returningDateTime' => new DateTime($contractData['returningDateTime']),
            'price' => $contractData['price']
        ]);

        $this->assertNotEmpty($createdContract, 'Le contrat n\'a pas été créé dans la base de données.');
    }

    public function testUpdateContract(): void
{
    // Nouvelles données pour la mise à jour du contrat
    $contractData = [];
    $contractData['signDateTime'] = "2024-05-20 12:01:00";
    $contractData['locBeginDateTime'] = "2024-05-20 12:01:00";
    $contractData['locEndDateTime'] = "2024-05-20 17:02:00";
    $contractData['returningDateTime'] = "2024-05-20 18:35:00";
    $contractData['price'] = 75;

    // Appel du service pour mettre à jour le contrat
    $this->contractService->updateContract($this->contractId, $contractData);

    // Récupération du contrat mis à jour depuis le repository
    $updatedContract = $this->contractRepository->find($this->contractId);

    // Vérifications des champs mis à jour
    $this->assertEquals(new DateTime($contractData['signDateTime']), $updatedContract->getSignDateTime());
    $this->assertEquals(new DateTime($contractData['locBeginDateTime']), $updatedContract->getLocBeginDateTime());
    $this->assertEquals(new DateTime($contractData['locEndDateTime']), $updatedContract->getLocEndDateTime());
    $this->assertEquals(new DateTime($contractData['returningDateTime']), $updatedContract->getReturningDateTime());
    $this->assertEquals($contractData['price'], $updatedContract->getPrice());
}

public function testGetContract(): void
{
    $contract = $this->contractService->getContract($this->contractId);
    $this->assertNotNull($contract, 'this is not null');
}

public function testDeleteContract() : void
{
    $this->contractService->deleteContract($this->contractId);
    $deletedContract = $this->contractRepository->find($this->contractId);
    $this->assertNull($deletedContract, 'Le billing n\'a pas été supprimé de la base de données.');
}

public function testGetLateContract(): void 
{
    // Arrange: Charger les contrats en retard à partir des fixtures
    $lateContracts = $this->contractService->getLateContracts();
    
    // Act: Vérifier le nombre de contrats en retard
    $lateContractsCount = count($lateContracts);
    
    // Assert: Confirmer que le nombre de contrats en retard est égal à 2
    $expectedCount = 3;
    $this->assertCount($expectedCount, $lateContracts, "Expected $expectedCount late contracts, but found $lateContractsCount.");
}

public function testIsLateContract(): void
{
    // Arrange: Obtenir le contrat en retard à partir du repository
    $lateContract = $this->contractRepository->find($this->contractId);
    
    // Act: Vérifier si le contrat est en retard
    $isLate = $this->contractService->isLateContract($lateContract);
    
    // Assert: Confirmer que le contrat est en retard
    $this->assertTrue($isLate, 'The contract is expected to be late.');
}


public function testGetBillingsFromContractId(): void
{
    // Arrange: Récupérer les facturations du contrat à partir du service
    $billings = $this->contractService->getBillingsFromContractId($this->contractId);
    
    // Act: Vérifier le nombre de facturations et leur montant
    $expectedCount = 1;
    $this->assertCount($expectedCount, $billings, "Expected $expectedCount billing(s) associated with the contract.");

    // Assert: Vérifier le montant de la facturation
    $billingAmount = $billings[0]->getAmount(); // supposons que le test passe, donc 1 billing placé à l'index 0
    $expectedAmount = 50;
    $this->assertEquals($expectedAmount, $billingAmount, "Expected billing amount of $expectedAmount.");
}

public function testCheckContractIsPaid(): void
{
    // $this->contractId est un contrat payé (prix contrat : 50 / 1 facture d'un montran 50 associée au contrat)
    $check = $this->contractService->checkContractIsPaid($this->contractId);

    $isPaid = $check['isPaid'];
    $expectedIsPaid = true;

    $remainingAmount = $check['remainingAmount'];
    $expectedRemainingAmount = 0;

    $this->assertTrue($isPaid, 'contrat payé');
    $this->assertEquals($expectedRemainingAmount, $remainingAmount, 'zero à payer restant');
}

public function testGetUnPaidContracts() : void
{
    $unpdaidContracts = $this->contractService->getUnpaidContracts();
    $expectedUnPaidContract = 2;
    $this->assertCount($expectedUnPaidContract, $unpdaidContracts, 'unpaid contract verified');

}

public function testCountLateContractBetween() : void
{
    $intervalDate1 = "2024-05-20 08:00:00";
    $intervalDate2 = "2024-05-23 08:00:00";
    $count = $this->contractService->countLateContractBetween($intervalDate1, $intervalDate2);
    $expectedCount = 3;
    $this->assertEquals($expectedCount, $count, "count late contract verified");
}


// -------------------- TEST VEHICLE SERVICE ----------------------- //

public function testCreateVehicle():void
{
    $vehicleDatas = [];
    $vehicleDatas['km'] = 10000;
    $vehicleDatas['informations'] = 'état correct';
    $vehicleDatas['plateNumber'] = 'bbd325';

    $this->vehicleService->createVehicle($vehicleDatas);
    
    $createdVehicle = $this->documentManager->getRepository(Vehicle::class)->findOneBy([
        'km' => 10000,
        'informations' => 'état correct',
        'plateNumber' => 'bbd325'
    ]);
    $this->assertNotNull($createdVehicle, 'create vehicle verified');
}

public function testCollectionExist() : void
{
    $exist = $this->vehicleService->collectionExists('Vehicle');
    $conditionToVerifiy = $exist == true;
    $this->assertTrue($conditionToVerifiy, 'collection exist verified');
}

public function testUpdateVehicle(): void
{
    $vehicleDatas = [];
    $vehicleDatas['km'] = 100;
    $vehicleDatas['informations'] = 'comme neuf';
    $vehicleDatas['plateNumber'] = 'dqs554';
    
    $this->vehicleService->updateVehicle('664dd594b23d381f0f2933e4', $vehicleDatas);
    $updatedVehicle = $this->documentManager->getRepository(Vehicle::class)->findOneBy([
        'km' => 100,
        'informations' => 'comme neuf',
        'plateNumber' => 'dqs554'
    ]);
    $this->assertNotNull($updatedVehicle, 'updateVehicle verified');
}

public function testDeleteVehicle(): void
{
    // Suppression du véhicule avec l'ID donné
    $message = $this->vehicleService->deleteVehicle('664dd594b23d381f0f2933e4');
    
    // Vérification si le message retourné est 'vehicle deleted'
    $expectedMessage = 'vehicle deleted';
    $this->assertEquals($expectedMessage, $message, 'The message returned by deleteVehicle is "vehicle deleted".');
}

public function testGetVehicle() : void
{
    $vehicle = $this->vehicleService->getVehicle('lkk789');
    $this->assertNotNull($vehicle);
    $plateNumber = $vehicle->getPlateNumber();
    $this->assertEquals($plateNumber, 'lkk789');
}

public function testCountVehicle():  void
{
    $count = $this->vehicleService->countVehicle(200000);

    // Vérifie si le résultat retourné par countVehicle est égal au nombre attendu
    $expectedCount = 1;
    $this->assertEquals($expectedCount, $count, "The number of vehicles with a mileage of 200000 km is $expectedCount.");
}

public function testGetLateTimeOnAverageByVehicle():void
{
    $lateTimeAverageByVehicle = $this->vehicleService->getLateTimeOnAverageByVehicle();
    $expectedCount = 2;
    $this->assertCount($expectedCount, $lateTimeAverageByVehicle, 'count verified');
    $this->assertIsArray($lateTimeAverageByVehicle, 'is array verified');
}

public function testGetContractFromVehicleId() : void
{
    $contracts = $this->vehicleService->getContractsFromVehicleId('664dd594b23d381f0f2933e4');
    $expectedCount = 2;
    $this->assertCount($expectedCount, $contracts);
}

public function testGetContractsGroupByVehicle() : void
{
    $listContractsGroupByVehicle = $this->vehicleService->getContractsGroupByVehicle();
    $key1 = '664dd594b23d381f0f2933e4';
    $this->assertArrayHasKey($key1, $listContractsGroupByVehicle, 'array has key');
    $key2 = '664dd5cfb23d381f0f2933e5';
    $this->assertArrayHasKey($key2, $listContractsGroupByVehicle, 'array has key');
}

public function testConvertTimeToMinutes() : void
{
    // Test format HH:MM
    $time = '02:00';
    $expectedTimeToMinuteConversion = 120;
    $minutes = $this->vehicleService->convertTimeToMinutes($time);
    $this->assertEquals($expectedTimeToMinuteConversion, $minutes, 'convert time (in hours) to minutes verified');

    // Test format DD:HH:MM
    $timeDays = '02:00:00';
    $expectedTimeToMinuteConversion2 = 2880;
    $minutes2 = $this->vehicleService->convertTimeToMinutes($timeDays);
    $this->assertEquals($expectedTimeToMinuteConversion2, $minutes2, 'convert time (in days) to minutes verified');
}


public function testConvertMinutesToTime() : void
{
    $minutes = 120;
    $expectedTime = '02:00';
    $time = $this->vehicleService->convertMinutesToTime($minutes);

    $minutes2 = 2880;
    $expecedTime2 = '48:00';
    $time2 = $this->vehicleService->convertMinutesToTime($minutes2);

    $this->assertEquals($expectedTime, $time, 'convert minutes(int) to time verified');
    $this->assertEquals($expecedTime2, $time2, 'convert minutes(int) to time verified');
}


// ---------------- CUSTOMER TEST ----------------- //


public function testCollectionExist_customer():void
{
    $exist = true;
    $conditionToVerify = $exist;
    $doExist = $this->customerService->collectionExist('Customer');
    $this->assertTrue($doExist, 'test collection exist verified');
    $this->assertEquals($conditionToVerify, $doExist, 'test collection exist verified');
}

public function testCreateCustomer() : void
{
    // Préparation des données du client
    $customerData = [
        'firstName' => 'jean-patrick',
        'lastName' => 'lanterne',
        'adress' => '2 rue du pré 30030 la-tour-du-pré',
        'permitNumber' => '123432'
    ];

    // Création du client via le service
    $createdCustomer = $this->customerService->createCustomer($customerData);

    // Vérification que l'objet Customer est bien créé
    $this->assertNotNull($createdCustomer->getId(), 'Customer ID should not be null after creation');
    $this->assertEquals($customerData['firstName'], $createdCustomer->getFirstName(), 'First name should match');
    $this->assertEquals($customerData['lastName'], $createdCustomer->getLastName(), 'Last name should match');
    $this->assertEquals($customerData['adress'], $createdCustomer->getAdress(), 'Address should match');
    $this->assertEquals($customerData['permitNumber'], $createdCustomer->getPermitNumber(), 'Permit number should match');

    // Vérification que le client est bien enregistré dans la base de données
    $retrievedCustomer = $this->documentManager->getRepository(Customer::class)->find($createdCustomer->getId());
    $this->assertNotNull($retrievedCustomer, 'Customer should be retrievable from the database');
    $this->assertEquals($customerData['firstName'], $retrievedCustomer->getFirstName(), 'First name should match in DB');
    $this->assertEquals($customerData['lastName'], $retrievedCustomer->getLastName(), 'Last name should match in DB');
    $this->assertEquals($customerData['adress'], $retrievedCustomer->getAdress(), 'Address should match in DB');
    $this->assertEquals($customerData['permitNumber'], $retrievedCustomer->getPermitNumber(), 'Permit number should match in DB');
}

public function testUpdateCustomer() : void
{
    $customer = $this->documentManager->getRepository(Customer::class)->find($this->customerId);
    $customerData = [
        'firstName' => 'sophie',
        'lastName' => 'brin d acier',
        'adress' => 'rue du champs qui chante 30039 remiremont',
        'permitNumber' => '111111'
    ];
    $updatedCustomer = $this->customerService->updateCustomer($this->customerId, $customerData);
    $this->documentManager->getRepository(Customer::class)->findBy([
        'firstName' => 'sophie',
        'lastName' => 'brin d acier',
        'adress' => 'rue du champs qui chante 30039 remiremont',
        'permitNumber' => '111111'
    ]);
    $this->assertNotNull($updatedCustomer, 'updateCustomer verified');
}


public function testDeleteCustomer() : void
{
    // Récupère le client à partir de la base de données
    $customer = $this->documentManager->getRepository(Customer::class)->find($this->customerId);
    
    // Vérifie que le client existe avant de le supprimer
    $this->assertNotNull($customer, 'Customer should exist before deletion');
    
    // Supprime le client
    $this->customerService->deleteCustomer($customer->getId());
    
    // Rafraîchit l'état du DocumentManager pour refléter les changements
    $this->documentManager->flush();
    
    // Récupère le client à nouveau pour vérifier qu'il a été supprimé
    $deletedCustomer = $this->documentManager->getRepository(Customer::class)->find($this->customerId);
    
    // Vérifie que le client n'existe plus
    $this->assertNull($deletedCustomer, 'deleteCustomer verified');
}

public function testGetCustomer(): void
{
    // Récupère le client initialement pour obtenir ses informations
    $initialCustomer = $this->documentManager->getRepository(Customer::class)->find($this->customerId);
    
    // Vérifie que le client initial existe bien
    $this->assertNotNull($initialCustomer, 'Initial customer should exist');
    
    // Récupère le prénom et le nom du client
    $firstName = $initialCustomer->getFirstName();
    $lastName = $initialCustomer->getLastName();
    
    // Utilise le service pour récupérer le client à partir du prénom et du nom
    $retrievedCustomer = $this->customerService->getCustomer($firstName, $lastName);
    
    // Vérifie que le client a bien été récupéré
    $this->assertNotNull($retrievedCustomer, 'Customer should be retrieved');
    
    // Vérifie que le client récupéré est bien le même que l'initial
    $this->assertEquals($initialCustomer->getId(), $retrievedCustomer->getId(), 'Retrieved customer should have the same ID as the initial customer');
}

public function testGetContractFromCustomerId(): void
{
    // Récupération du customerId depuis les fixtures
    $customerId = $this->customerId;
    $this->assertNotNull($customerId, 'customer ID not null verified');

    // Récupération du client à partir de l'ID
    $customer = $this->documentManager->getRepository(Customer::class)->find($customerId);
    $this->assertNotNull($customer, 'customer not null verified');

    // Nombre attendu de contrats pour ce client (à ajuster si nécessaire)
    $expectedContractCount = 2;

    // Récupération des contrats via le service
    $contracts = $this->customerService->getContractFromCustomerId($customerId);
    $this->assertEquals($expectedContractCount, count($contracts), 'getContractsFromCustomerId verified');

    // Récupération des contrats via le repository
    $listContractByRepo = $this->contractRepository->findBy(['customerId' => $customerId]);

    // Vérification que le nombre de contrats est le même dans les trois listes
    $this->assertCount(count($listContractByRepo), $contracts, 'Number of contracts from service matches number from repository');
}

public function testGetCurrentContractsFromCustomer():void
{
    $contracts = $this->contractRepository->findBy(['customerId' => $this->customerId]);
    $currentContrats = $this->customerService->getCurrentContractsFromCustomer($contracts);
    $this->assertEmpty($currentContrats, 'current contract array is empty : verified');
    $totalOnGoingContractExpected = 0;
    $this->assertEquals($totalOnGoingContractExpected, count($currentContrats), 'getcurrentcontractfromcustomer verified');
}

public function testGetLateContractsOnAverageByCustomer():void
{
    $lateOnAverage = $this->customerService->getLateContractsOnAverageByCustomer($this->contractService);
    $totalCustomerExpected = 2;
    $totalCustomer = count($lateOnAverage);
    $this->assertEquals($totalCustomerExpected, $totalCustomer, 'total customer verified in avergelateCustomer array');
    $this->assertArrayHasKey('late_on_average', $lateOnAverage['id'.$this->customerId], 'array has key verified');
}

public function testGetContractsGroupByCustomer() : void
{
    $contractsGroupByCustomer = $this->customerService->getContractsGroupByCustomer();
    $this->assertIsArray($contractsGroupByCustomer, 'service return response as array : verified');
    $this->assertArrayHasKey('customer'.$this->customerId, $contractsGroupByCustomer, 'service return array with key as expected : verified');
    $totalContractExpected = 2;
    $totalContractActuel = count($contractsGroupByCustomer['customer'.$this->customerId]);
    $this->assertEquals($totalContractExpected, $totalContractActuel, 'service return array with total nb contracts for customer '.$this->customerId.' as expected');
}

}
