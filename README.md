# Documentation PROJECT API SQL - NOSQL (study project)

## Introduction

This project is an **API** that facilitates handling requests between **two distinct database models: SQL and document-oriented NoSQL**.

## Features
Classic CRUD operations: create, update, retrieve, and delete records/documents.
Cross-database queries.

## Technologies Used
- Databases: MongoDB and SQL Server
- Framework: Symfony 7.0.6
- Language: PHP 8.2
- Key dependencies: Doctrine ORM, Doctrine MongoDB ODM Bundle, Lexik JWT Authentication

## Configuration and Customization
You can adapt the project settings to meet your API's specific needs: routes, controllers, services, entities, documents.

## Installation
Dependencies
Run `composer install` to install project dependencies.

## Databases
Connection Configuration
In the .env file, specify the connection information.

- SQL Server: DATABASE_URL="driver://user:password@host:port/dbname"
- MongoDB Atlas: MONGODB_URL=mongodb://user:password@host/?options MONGODB_DB=dbname

## Schema Definition

Entity directory: define the schema for your SQL Server records.
Document directory: define the schema for your MongoDB records.
Schemas define properties, getters, and setters for your entities/documents, mapping properties to corresponding fields in the tables/collections.

## Generating Production Databases

- Scenario 1: Database not yet generated

**SQL Server** run `php bin/console doctrine:database:create`
**MongoDB** persist your documents generates the database and collection.

- Scenario 2: Databases already generated

Ready to store data.

## Generating Test Databases

Create a .env.test file and specify connection information.
- SQL Server: name your test DB {dbname}_test.
- MongoDB: no specific naming convention required.

## Inject test data:

- SQL Server: `php bin/console doctrine:fixtures:load --env=test` or `php bin/console d:f:l --env=test`
- MongoDB: `php bin/console doctrine:mongodb:fixtures:load --env=test` or `php bin/console d:m:f:l --env=test`

## Routes

Define your API routes in the routes.yaml file.

A route is defined by a name, URL, controller, method, and HTTP method.

Example:

`update_customer:
  path: /api/customers
  controller: App\Controller\CustomerController::updateCustomer
  methods: ['PUT']`

**Thoses are the API endpoints.**

## Controllers and Services

The controller handles the API's response to client requests. The service is a standalone function. 

To adhere to the MVC model, isolate web logic in the controller and data processing in the service.

    `#[Route('/api/customers/{customerId}', name: 'delete_customer', methods: ['DELETE'])]
    public function deleteCustomer($customerId) 
    {
        // injecte SERVICE deleteCustomer
        // retourne au format JSON la réponse du service
        $response = $this->customerService->deleteCustomer($customerId);
        return new JsonResponse($response);
    }`

    `public function deleteCustomer(string $customerId) 
    {
        // recherche l'objet customer via correspondance à partir de l'ID customer
        // i
        $customer = $this->dm->getRepository(Customer::class)->find($customerId);
        if (!$customer) {
            throw new InvalidArgumentException('Customer not found for ID ' . $customerId);
        }
        $this->dm->remove($customer);
        $this->dm->flush();
        // retourne un tableau json
        return ['message' => 'operation successfull : customer deleted'];
    }`


**Our API returns a JSON response to the client.**

## Tests

The project comes with a set of unit tests and logic for injecting fixtures before each test
