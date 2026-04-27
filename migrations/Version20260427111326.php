<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260427111326 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add fingerprint, owner_key, predecessor link to listing for relisted-car detection';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE listing ADD fingerprint VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE listing ADD owner_key VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE listing ADD predecessor_match_type VARCHAR(30) DEFAULT NULL');
        $this->addSql('ALTER TABLE listing ADD predecessor_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE listing ADD CONSTRAINT FK_CB0048D468C90015 FOREIGN KEY (predecessor_id) REFERENCES listing (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_CB0048D468C90015 ON listing (predecessor_id)');
        $this->addSql('CREATE INDEX idx_listing_fingerprint ON listing (fingerprint)');
        $this->addSql('CREATE INDEX idx_listing_owner_key ON listing (owner_key)');

        // Backfill fingerprint and owner_key from raw_data for existing rows.
        $this->addSql(<<<'SQL'
            UPDATE listing SET fingerprint = concat_ws('|',
                COALESCE(NULLIF(TRIM(additional_model_name), ''), '?'),
                COALESCE(to_char(manufacturing_date, 'YYYY-MM-DD'), '?'),
                COALESCE(to_char(in_operation_date, 'YYYY-MM-DD'), '?'),
                COALESCE(NULLIF(TRIM(gearbox), ''), '?'),
                COALESCE(NULLIF(TRIM(fuel), ''), '?'),
                COALESCE(NULLIF(TRIM(location_district), ''), '?')
            )
        SQL);

        $this->addSql(<<<'SQL'
            UPDATE listing SET owner_key = CASE
                WHEN raw_data::jsonb->'premise'->>'id' IS NOT NULL
                     AND NULLIF(TRIM(raw_data::jsonb->>'custom_id'), '') IS NOT NULL
                    THEN concat('premise:', raw_data::jsonb->'premise'->>'id', ':', raw_data::jsonb->>'custom_id')
                WHEN raw_data::jsonb->'premise'->>'id' IS NOT NULL
                    THEN concat('premise:', raw_data::jsonb->'premise'->>'id')
                WHEN raw_data::jsonb->'user'->>'id' IS NOT NULL
                    THEN concat('user:', raw_data::jsonb->'user'->>'id')
                ELSE 'none'
            END
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE listing DROP CONSTRAINT FK_CB0048D468C90015');
        $this->addSql('DROP INDEX IDX_CB0048D468C90015');
        $this->addSql('DROP INDEX idx_listing_fingerprint');
        $this->addSql('DROP INDEX idx_listing_owner_key');
        $this->addSql('ALTER TABLE listing DROP fingerprint');
        $this->addSql('ALTER TABLE listing DROP owner_key');
        $this->addSql('ALTER TABLE listing DROP predecessor_match_type');
        $this->addSql('ALTER TABLE listing DROP predecessor_id');
    }
}
