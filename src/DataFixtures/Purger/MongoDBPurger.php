<?php

// src/DataFixtures/Purger/MongoDBPurger.php

namespace App\DataFixtures\Purger;

use Doctrine\Common\DataFixtures\Purger\PurgerInterface;
use Doctrine\ODM\MongoDB\DocumentManager;

class MongoDBPurger implements PurgerInterface
{
    private DocumentManager $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    /**
     * Purge la base de données MongoDB en supprimant le contenu des collections.
     */
    public function purge(): void
    {
        $this->documentManager->getSchemaManager()->dropDocumentCollection();
        $this->documentManager->getSchemaManager()->createDocumentCollections();
    }

    /**
     * Purge the data storage.
     *
     * @return void
     */

    /**
     * Sets the DocumentManager instance to use.
     *
     * @param DocumentManager $dm
     */
    public function setDocumentManager(DocumentManager $dm): void
    {
        $this->documentManager = $dm;
    }
}
