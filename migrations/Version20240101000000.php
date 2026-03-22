<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create site_records table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE site_records (
                id               VARCHAR(36)   NOT NULL,
                name             VARCHAR(255)  NOT NULL,
                url              VARCHAR(500)  NOT NULL,
                status           VARCHAR(50)   NOT NULL,
                response_time_ms INT           NOT NULL DEFAULT 0,
                notes            LONGTEXT      DEFAULT NULL,
                created_at       DATETIME      NOT NULL,
                updated_at       DATETIME      NOT NULL,
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ');

        $this->addSql('CREATE INDEX idx_site_records_status ON site_records (status)');
        $this->addSql('CREATE INDEX idx_site_records_created_at ON site_records (created_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE site_records');
    }
}
