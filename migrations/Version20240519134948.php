<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240519134948 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE appointment (id INT AUTO_INCREMENT NOT NULL, treatment_id INT NOT NULL, barber_id INT NOT NULL, customer_id INT NOT NULL, INDEX IDX_FE38F844471C0366 (treatment_id), INDEX IDX_FE38F844BFF2FEF2 (barber_id), INDEX IDX_FE38F8449395C3F3 (customer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F844471C0366 FOREIGN KEY (treatment_id) REFERENCES treatment (id)');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F844BFF2FEF2 FOREIGN KEY (barber_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F8449395C3F3 FOREIGN KEY (customer_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F844471C0366');
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F844BFF2FEF2');
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F8449395C3F3');
        $this->addSql('DROP TABLE appointment');
    }
}
