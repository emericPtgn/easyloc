<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240511213452 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Drop the primary key constraint
        
        // Alter the column to make it auto-increment
        $this->addSql('ALTER TABLE commande ALTER COLUMN id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Alter the column to remove auto-increment
        $this->addSql('ALTER TABLE commande ALTER COLUMN id NVARCHAR(255) NOT NULL');
        
        // Add back the primary key constraint
    }

}
