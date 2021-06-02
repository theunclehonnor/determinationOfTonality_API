<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210602120942 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE report DROP CONSTRAINT fk_c42f778482b27cf0');
        $this->addSql('DROP INDEX uniq_c42f778482b27cf0');
        $this->addSql('ALTER TABLE report DROP object_in_question_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE report ADD object_in_question_id INT NOT NULL');
        $this->addSql('ALTER TABLE report ADD CONSTRAINT fk_c42f778482b27cf0 FOREIGN KEY (object_in_question_id) REFERENCES object_in_question (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_c42f778482b27cf0 ON report (object_in_question_id)');
    }
}
