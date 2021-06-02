<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210602113220 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE model DROP CONSTRAINT fk_d79572d994aab322');
        $this->addSql('DROP SEQUENCE list_models_id_seq CASCADE');
        $this->addSql('DROP TABLE list_models');
        $this->addSql('ALTER TABLE model DROP CONSTRAINT fk_d79572d982b27cf0');
        $this->addSql('DROP INDEX idx_d79572d994aab322');
        $this->addSql('DROP INDEX idx_d79572d982b27cf0');
        $this->addSql('ALTER TABLE model ADD data_set VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE model ADD classificator VARCHAR(150) NOT NULL');
        $this->addSql('ALTER TABLE model ADD description VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE model ADD path VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE model DROP object_in_question_id');
        $this->addSql('ALTER TABLE model DROP list_models_id');
        $this->addSql('ALTER TABLE model ALTER name TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE object_in_question ADD model_id INT NOT NULL');
        $this->addSql('ALTER TABLE object_in_question ADD CONSTRAINT FK_33AA551C7975B7E7 FOREIGN KEY (model_id) REFERENCES model (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_33AA551C7975B7E7 ON object_in_question (model_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE SEQUENCE list_models_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE list_models (id INT NOT NULL, name VARCHAR(255) NOT NULL, data_set VARCHAR(255) NOT NULL, classificator VARCHAR(150) NOT NULL, description VARCHAR(500) DEFAULT NULL, path VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE object_in_question DROP CONSTRAINT FK_33AA551C7975B7E7');
        $this->addSql('DROP INDEX IDX_33AA551C7975B7E7');
        $this->addSql('ALTER TABLE object_in_question DROP model_id');
        $this->addSql('ALTER TABLE model ADD object_in_question_id INT NOT NULL');
        $this->addSql('ALTER TABLE model ADD list_models_id INT NOT NULL');
        $this->addSql('ALTER TABLE model DROP data_set');
        $this->addSql('ALTER TABLE model DROP classificator');
        $this->addSql('ALTER TABLE model DROP description');
        $this->addSql('ALTER TABLE model DROP path');
        $this->addSql('ALTER TABLE model ALTER name TYPE VARCHAR(500)');
        $this->addSql('ALTER TABLE model ADD CONSTRAINT fk_d79572d982b27cf0 FOREIGN KEY (object_in_question_id) REFERENCES object_in_question (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE model ADD CONSTRAINT fk_d79572d994aab322 FOREIGN KEY (list_models_id) REFERENCES list_models (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_d79572d994aab322 ON model (list_models_id)');
        $this->addSql('CREATE INDEX idx_d79572d982b27cf0 ON model (object_in_question_id)');
    }
}
