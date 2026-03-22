<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240101000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create messenger_messages table for async doctrine transport';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE messenger_messages (
                id           BIGINT        NOT NULL AUTO_INCREMENT,
                body         LONGTEXT      NOT NULL,
                headers      LONGTEXT      NOT NULL,
                queue_name   VARCHAR(190)  NOT NULL,
                created_at   DATETIME      NOT NULL,
                available_at DATETIME      NOT NULL,
                delivered_at DATETIME      DEFAULT NULL,
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ');

        $this->addSql('CREATE INDEX idx_messenger_queue     ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX idx_messenger_available ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX idx_messenger_delivered ON messenger_messages (delivered_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE messenger_messages');
    }
}
