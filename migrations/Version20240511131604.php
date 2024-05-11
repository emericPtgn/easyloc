<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240511131604 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE billing (id INT IDENTITY NOT NULL, contract_id INT NOT NULL, amount NUMERIC(10, 2) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_EC224CAA2576E0FD ON billing (contract_id)');
        $this->addSql('CREATE TABLE contract (id INT IDENTITY NOT NULL, vehicle_id NVARCHAR(255) NOT NULL, customer_id NVARCHAR(255) NOT NULL, sign_date_time DATETIME2(6), loc_begin_date_time DATETIME2(6) NOT NULL, loc_end_date_time DATETIME2(6) NOT NULL, returning_date_time DATETIME2(6), price NUMERIC(10, 2) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE billing ADD CONSTRAINT FK_EC224CAA2576E0FD FOREIGN KEY (contract_id) REFERENCES contract (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA db_accessadmin');
        $this->addSql('CREATE SCHEMA db_backupoperator');
        $this->addSql('CREATE SCHEMA db_datareader');
        $this->addSql('CREATE SCHEMA db_datawriter');
        $this->addSql('CREATE SCHEMA db_ddladmin');
        $this->addSql('CREATE SCHEMA db_denydatareader');
        $this->addSql('CREATE SCHEMA db_denydatawriter');
        $this->addSql('CREATE SCHEMA db_owner');
        $this->addSql('CREATE SCHEMA db_securityadmin');
        $this->addSql('CREATE SCHEMA dbo');
        $this->addSql('ALTER TABLE billing DROP CONSTRAINT FK_EC224CAA2576E0FD');
        $this->addSql('DROP TABLE billing');
        $this->addSql('DROP TABLE contract');
    }
}
