<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210602131154 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE report ALTER file DROP NOT NULL');
        $this->addSql('ALTER TABLE resource DROP CONSTRAINT fk_bc91f41682b27cf0');
        $this->addSql('DROP INDEX uniq_bc91f41682b27cf0');
        $this->addSql('ALTER TABLE resource DROP object_in_question_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE report ALTER file SET NOT NULL');
        $this->addSql('ALTER TABLE resource ADD object_in_question_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE resource ADD CONSTRAINT fk_bc91f41682b27cf0 FOREIGN KEY (object_in_question_id) REFERENCES object_in_question (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_bc91f41682b27cf0 ON resource (object_in_question_id)');
    }
}
