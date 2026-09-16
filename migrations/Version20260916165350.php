<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260916165350 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow the rating column of the review table to be temporarily null while the form is being validated.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE review ALTER rating DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE review ALTER rating SET NOT NULL');
    }
}
