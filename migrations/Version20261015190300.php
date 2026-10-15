<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015190300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create projects and project media tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE projet (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, categorie VARCHAR(30) NOT NULL, description LONGTEXT NOT NULL, technologies VARCHAR(500) DEFAULT NULL, lien VARCHAR(500) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_4A93A1D6989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE projet_media (id INT AUTO_INCREMENT NOT NULL, projet_id INT NOT NULL, nom_fichier VARCHAR(255) NOT NULL, nom_original VARCHAR(255) NOT NULL, type_mime VARCHAR(100) NOT NULL, position INT NOT NULL, INDEX IDX_474D3B2E79F3FC6A (projet_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE projet_media ADD CONSTRAINT FK_474D3B2E79F3FC6A FOREIGN KEY (projet_id) REFERENCES projet (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE projet_media DROP FOREIGN KEY FK_474D3B2E79F3FC6A');
        $this->addSql('DROP TABLE projet_media');
        $this->addSql('DROP TABLE projet');
    }
}
