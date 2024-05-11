<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240511132635 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE billing DROP CONSTRAINT FK_EC224CAA2576E0FD');
        $this->addSql('DROP INDEX IDX_EC224CAA2576E0FD ON billing');
        $this->addSql('sp_rename \'billing.contract_id\', \'contractId\', \'COLUMN\'');
        $this->addSql('ALTER TABLE billing ADD CONSTRAINT FK_EC224CAA7471E49A FOREIGN KEY (contractId) REFERENCES contract (id)');
        $this->addSql('CREATE INDEX IDX_EC224CAA7471E49A ON billing (contractId)');
        $this->addSql('ALTER TABLE contract ADD vehicleId NVARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE contract ADD customerId NVARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE contract ADD signDateTime DATETIME2(6)');
        $this->addSql('ALTER TABLE contract ADD locBeginDateTime DATETIME2(6) NOT NULL');
        $this->addSql('ALTER TABLE contract ADD locEndDateTime DATETIME2(6) NOT NULL');
        $this->addSql('ALTER TABLE contract ADD returningDateTime DATETIME2(6)');
        $this->addSql('ALTER TABLE contract DROP COLUMN vehicle_id');
        $this->addSql('ALTER TABLE contract DROP COLUMN customer_id');
        $this->addSql('ALTER TABLE contract DROP COLUMN sign_date_time');
        $this->addSql('ALTER TABLE contract DROP COLUMN loc_begin_date_time');
        $this->addSql('ALTER TABLE contract DROP COLUMN loc_end_date_time');
        $this->addSql('ALTER TABLE contract DROP COLUMN returning_date_time');
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
        $this->addSql('ALTER TABLE billing DROP CONSTRAINT FK_EC224CAA7471E49A');
        $this->addSql('DROP INDEX IDX_EC224CAA7471E49A ON billing');
        $this->addSql('sp_rename \'billing.contractId\', \'contract_id\', \'COLUMN\'');
        $this->addSql('ALTER TABLE billing ADD CONSTRAINT FK_EC224CAA2576E0FD FOREIGN KEY (contract_id) REFERENCES contract (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE NONCLUSTERED INDEX IDX_EC224CAA2576E0FD ON billing (contract_id)');
        $this->addSql('ALTER TABLE contract ADD vehicle_id NVARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE contract ADD customer_id NVARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE contract ADD sign_date_time DATETIME2(6)');
        $this->addSql('ALTER TABLE contract ADD loc_begin_date_time DATETIME2(6) NOT NULL');
        $this->addSql('ALTER TABLE contract ADD loc_end_date_time DATETIME2(6) NOT NULL');
        $this->addSql('ALTER TABLE contract ADD returning_date_time DATETIME2(6)');
        $this->addSql('ALTER TABLE contract DROP COLUMN vehicleId');
        $this->addSql('ALTER TABLE contract DROP COLUMN customerId');
        $this->addSql('ALTER TABLE contract DROP COLUMN signDateTime');
        $this->addSql('ALTER TABLE contract DROP COLUMN locBeginDateTime');
        $this->addSql('ALTER TABLE contract DROP COLUMN locEndDateTime');
        $this->addSql('ALTER TABLE contract DROP COLUMN returningDateTime');
    }
}
