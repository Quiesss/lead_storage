<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230212084050 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE leads ADD buyer_id INT DEFAULT NULL, ADD status VARCHAR(255) NOT NULL, ADD other JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE leads ADD CONSTRAINT FK_179045526C755722 FOREIGN KEY (buyer_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_179045526C755722 ON leads (buyer_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE leads DROP FOREIGN KEY FK_179045526C755722');
        $this->addSql('DROP INDEX IDX_179045526C755722 ON leads');
        $this->addSql('ALTER TABLE leads DROP buyer_id, DROP status, DROP other');
    }
}
