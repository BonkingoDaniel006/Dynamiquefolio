<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015190400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align project indexes and immutable datetime metadata with Doctrine.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE projet CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE projet RENAME INDEX UNIQ_4A93A1D6989D9B62 TO UNIQ_50159CA9989D9B62');
        $this->addSql('ALTER TABLE projet_media RENAME INDEX IDX_474D3B2E79F3FC6A TO IDX_C18A4EBAC18272');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE projet_media RENAME INDEX IDX_C18A4EBAC18272 TO IDX_474D3B2E79F3FC6A');
        $this->addSql('ALTER TABLE projet RENAME INDEX UNIQ_50159CA9989D9B62 TO UNIQ_4A93A1D6989D9B62');
        $this->addSql('ALTER TABLE projet CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
