<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260908180223 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE admin_user_stores (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, store_id INT NOT NULL, UNIQUE INDEX uniq_user_store (user_id, store_id), INDEX IDX_2DD82EF8A76ED395 (user_id), INDEX IDX_2DD82EF8B092A811 (store_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE admin_users (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(190) NOT NULL, password VARCHAR(255) NOT NULL, roles JSON NOT NULL, name VARCHAR(120) NOT NULL, totp_secret VARCHAR(255) DEFAULT NULL, active TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_B4A95E13E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE attribute_values (id INT AUTO_INCREMENT NOT NULL, value VARCHAR(255) NOT NULL, attribute_id INT NOT NULL, UNIQUE INDEX uniq_attr_value (attribute_id, value), INDEX IDX_184662BCB6E62EFA (attribute_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE attributes (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(60) NOT NULL, name VARCHAR(120) NOT NULL, filterable TINYINT NOT NULL, UNIQUE INDEX UNIQ_319B9E7077153098 (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE audit_log (id INT AUTO_INCREMENT NOT NULL, actor VARCHAR(120) DEFAULT NULL, action VARCHAR(40) NOT NULL, entity_type VARCHAR(120) NOT NULL, entity_id INT DEFAULT NULL, changes JSON DEFAULT NULL, at DATETIME NOT NULL, INDEX idx_audit_entity (entity_type, entity_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE categories (id INT AUTO_INCREMENT NOT NULL, legacy_id INT DEFAULT NULL, position INT NOT NULL, show_in_menu TINYINT NOT NULL, published TINYINT NOT NULL, listing_layout VARCHAR(40) DEFAULT \'grid\' NOT NULL, store_id INT NOT NULL, parent_id INT DEFAULT NULL, INDEX idx_category_legacy (legacy_id), INDEX IDX_3AF34668B092A811 (store_id), INDEX IDX_3AF34668727ACA70 (parent_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE category_products (id INT AUTO_INCREMENT NOT NULL, position INT NOT NULL, category_id INT NOT NULL, product_id INT NOT NULL, UNIQUE INDEX uniq_category_product (category_id, product_id), INDEX IDX_4C0DE2112469DE2 (category_id), INDEX IDX_4C0DE214584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE category_translations (id INT AUTO_INCREMENT NOT NULL, locale VARCHAR(8) NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, meta_title VARCHAR(255) DEFAULT NULL, meta_description VARCHAR(255) DEFAULT NULL, body_html LONGTEXT DEFAULT NULL, content_source VARCHAR(255) DEFAULT \'template\' NOT NULL, locked TINYINT NOT NULL, category_id INT NOT NULL, UNIQUE INDEX uniq_category_locale (category_id, locale), INDEX IDX_1C60F91512469DE2 (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE customer_addresses (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(16) DEFAULT \'billing\' NOT NULL, is_default TINYINT NOT NULL, address_company VARCHAR(120) DEFAULT NULL, address_first_name VARCHAR(120) DEFAULT NULL, address_last_name VARCHAR(120) DEFAULT NULL, address_street VARCHAR(200) DEFAULT NULL, address_city VARCHAR(120) DEFAULT NULL, address_zip VARCHAR(20) DEFAULT NULL, address_country VARCHAR(2) DEFAULT \'CZ\' NOT NULL, address_phone VARCHAR(20) DEFAULT NULL, address_company_id VARCHAR(20) DEFAULT NULL, address_vat_id VARCHAR(20) DEFAULT NULL, customer_id INT NOT NULL, INDEX IDX_C4378D0C9395C3F3 (customer_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE customer_consents (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(40) NOT NULL, granted_at DATETIME NOT NULL, revoked_at DATETIME DEFAULT NULL, source VARCHAR(120) DEFAULT NULL, customer_id INT NOT NULL, INDEX IDX_2A1F8E489395C3F3 (customer_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE customer_stores (id INT AUTO_INCREMENT NOT NULL, first_seen_at DATETIME NOT NULL, customer_id INT NOT NULL, store_id INT NOT NULL, UNIQUE INDEX uniq_customer_store (customer_id, store_id), INDEX IDX_6455A09D9395C3F3 (customer_id), INDEX IDX_6455A09DB092A811 (store_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE customers (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(190) NOT NULL, password_hash VARCHAR(255) DEFAULT NULL, legacy_md5 VARCHAR(255) DEFAULT NULL, legacy_key VARCHAR(60) DEFAULT NULL, gender VARCHAR(8) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_62534E21E7927C74 (email), INDEX idx_customer_legacy (legacy_key), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE id_map (id INT AUTO_INCREMENT NOT NULL, source VARCHAR(60) NOT NULL, entity_type VARCHAR(60) NOT NULL, old_id VARCHAR(64) NOT NULL, new_id INT NOT NULL, INDEX idx_idmap_new (entity_type, new_id), UNIQUE INDEX uniq_idmap (source, entity_type, old_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE import_batches (id INT AUTO_INCREMENT NOT NULL, source VARCHAR(60) NOT NULL, step VARCHAR(60) NOT NULL, started_at DATETIME NOT NULL, finished_at DATETIME DEFAULT NULL, processed INT NOT NULL, skipped INT NOT NULL, report JSON DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE invoice_series (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(40) NOT NULL, prefix VARCHAR(20) NOT NULL, next_number INT NOT NULL, reset_yearly TINYINT NOT NULL, current_year INT NOT NULL, UNIQUE INDEX UNIQ_81269C7E77153098 (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE invoices (id INT AUTO_INCREMENT NOT NULL, number VARCHAR(40) NOT NULL, issued_on DATE NOT NULL, taxable_on DATE NOT NULL, due_on DATE NOT NULL, total INT NOT NULL, currency VARCHAR(8) NOT NULL, vat_mode VARCHAR(20) DEFAULT \'non_vat\' NOT NULL, qr_payment LONGTEXT DEFAULT NULL, pdf_path VARCHAR(500) DEFAULT NULL, status VARCHAR(20) DEFAULT \'issued\' NOT NULL, buyer_company VARCHAR(120) DEFAULT NULL, buyer_first_name VARCHAR(120) DEFAULT NULL, buyer_last_name VARCHAR(120) DEFAULT NULL, buyer_street VARCHAR(200) DEFAULT NULL, buyer_city VARCHAR(120) DEFAULT NULL, buyer_zip VARCHAR(20) DEFAULT NULL, buyer_country VARCHAR(2) DEFAULT \'CZ\' NOT NULL, buyer_phone VARCHAR(20) DEFAULT NULL, buyer_company_id VARCHAR(20) DEFAULT NULL, buyer_vat_id VARCHAR(20) DEFAULT NULL, series_id INT NOT NULL, order_id INT NOT NULL, supplier_id INT NOT NULL, UNIQUE INDEX UNIQ_6A2F2F9596901F54 (number), UNIQUE INDEX UNIQ_6A2F2F958D9F6D38 (order_id), INDEX IDX_6A2F2F955278319C (series_id), INDEX IDX_6A2F2F952ADD6D8C (supplier_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE legal_entities (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(160) NOT NULL, address VARCHAR(255) NOT NULL, company_id VARCHAR(20) NOT NULL, vat_id VARCHAR(20) DEFAULT NULL, vat_payer TINYINT NOT NULL, iban VARCHAR(34) DEFAULT NULL, bank_account VARCHAR(40) DEFAULT NULL, sampling_room VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE media (id INT AUTO_INCREMENT NOT NULL, path VARCHAR(500) NOT NULL, mime VARCHAR(100) NOT NULL, size INT NOT NULL, hash VARCHAR(64) DEFAULT NULL, alt VARCHAR(255) DEFAULT NULL, width INT DEFAULT NULL, height INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX idx_media_hash (hash), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE order_items (id INT AUTO_INCREMENT NOT NULL, name_snapshot VARCHAR(255) NOT NULL, variant_snapshot VARCHAR(120) DEFAULT NULL, sku_snapshot VARCHAR(64) DEFAULT NULL, quantity INT NOT NULL, unit_price INT NOT NULL, vat_rate INT DEFAULT NULL, commission INT NOT NULL, order_id INT NOT NULL, product_id INT DEFAULT NULL, variant_id INT DEFAULT NULL, variant_size_id INT DEFAULT NULL, INDEX IDX_62809DB08D9F6D38 (order_id), INDEX IDX_62809DB04584665A (product_id), INDEX IDX_62809DB03B69A9AF (variant_id), INDEX IDX_62809DB026288561 (variant_size_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE order_status_history (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(255) NOT NULL, changed_at DATETIME NOT NULL, changed_by VARCHAR(120) DEFAULT NULL, mail_sent TINYINT NOT NULL, mail_sent_at DATETIME DEFAULT NULL, promised_at DATETIME DEFAULT NULL, note LONGTEXT DEFAULT NULL, order_id INT NOT NULL, INDEX IDX_471AD77E8D9F6D38 (order_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE orders (id INT AUTO_INCREMENT NOT NULL, legacy_id INT DEFAULT NULL, code VARCHAR(24) NOT NULL, placed_at DATETIME NOT NULL, status VARCHAR(255) DEFAULT \'new\' NOT NULL, currency VARCHAR(8) NOT NULL, items_total INT NOT NULL, shipping_total INT NOT NULL, discount_total INT NOT NULL, grand_total INT NOT NULL, shipping_method_code VARCHAR(40) DEFAULT NULL, payment_method_code VARCHAR(40) DEFAULT NULL, email VARCHAR(190) DEFAULT NULL, customer_note LONGTEXT DEFAULT NULL, internal_note LONGTEXT DEFAULT NULL, coupon VARCHAR(50) DEFAULT NULL, source VARCHAR(120) DEFAULT NULL, pickup_point VARCHAR(255) DEFAULT NULL, tracking_number VARCHAR(60) DEFAULT NULL, carrier VARCHAR(40) DEFAULT NULL, promised_at DATETIME DEFAULT NULL, shipped_at DATETIME DEFAULT NULL, settled_at DATETIME DEFAULT NULL, billing_company VARCHAR(120) DEFAULT NULL, billing_first_name VARCHAR(120) DEFAULT NULL, billing_last_name VARCHAR(120) DEFAULT NULL, billing_street VARCHAR(200) DEFAULT NULL, billing_city VARCHAR(120) DEFAULT NULL, billing_zip VARCHAR(20) DEFAULT NULL, billing_country VARCHAR(2) DEFAULT \'CZ\' NOT NULL, billing_phone VARCHAR(20) DEFAULT NULL, billing_company_id VARCHAR(20) DEFAULT NULL, billing_vat_id VARCHAR(20) DEFAULT NULL, shipping_company VARCHAR(120) DEFAULT NULL, shipping_first_name VARCHAR(120) DEFAULT NULL, shipping_last_name VARCHAR(120) DEFAULT NULL, shipping_street VARCHAR(200) DEFAULT NULL, shipping_city VARCHAR(120) DEFAULT NULL, shipping_zip VARCHAR(20) DEFAULT NULL, shipping_country VARCHAR(2) DEFAULT \'CZ\' NOT NULL, shipping_phone VARCHAR(20) DEFAULT NULL, shipping_company_id VARCHAR(20) DEFAULT NULL, shipping_vat_id VARCHAR(20) DEFAULT NULL, store_id INT NOT NULL, customer_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_E52FFDEE77153098 (code), INDEX idx_order_legacy (store_id, legacy_id), INDEX IDX_E52FFDEEB092A811 (store_id), INDEX IDX_E52FFDEE9395C3F3 (customer_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE page_translations (id INT AUTO_INCREMENT NOT NULL, locale VARCHAR(8) NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, meta_title VARCHAR(255) DEFAULT NULL, meta_description VARCHAR(255) DEFAULT NULL, body_html LONGTEXT DEFAULT NULL, content_source VARCHAR(255) DEFAULT \'template\' NOT NULL, locked TINYINT NOT NULL, page_id INT NOT NULL, UNIQUE INDEX uniq_page_locale (page_id, locale), INDEX IDX_78AB76C9C4663E4 (page_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE pages (id INT AUTO_INCREMENT NOT NULL, legacy_id INT DEFAULT NULL, type VARCHAR(255) DEFAULT \'content\' NOT NULL, template VARCHAR(60) DEFAULT \'default\' NOT NULL, position INT NOT NULL, show_in_menu TINYINT NOT NULL, published TINYINT NOT NULL, store_id INT NOT NULL, parent_id INT DEFAULT NULL, INDEX idx_page_legacy (legacy_id), INDEX IDX_2074E575B092A811 (store_id), INDEX IDX_2074E575727ACA70 (parent_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE payment_methods (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(40) NOT NULL, gateway VARCHAR(255) NOT NULL, labels JSON NOT NULL, fee INT NOT NULL, enabled TINYINT NOT NULL, position INT NOT NULL, store_id INT NOT NULL, INDEX IDX_4FABF983B092A811 (store_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE prices (id INT AUTO_INCREMENT NOT NULL, price INT NOT NULL, price_before_discount INT DEFAULT NULL, purchase_price INT DEFAULT NULL, currency VARCHAR(8) NOT NULL, store_id INT NOT NULL, product_id INT NOT NULL, variant_id INT DEFAULT NULL, UNIQUE INDEX uniq_price_scope (store_id, product_id, variant_id), INDEX IDX_E4CB6D59B092A811 (store_id), INDEX IDX_E4CB6D594584665A (product_id), INDEX IDX_E4CB6D593B69A9AF (variant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE product_attribute_values (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, attribute_value_id INT NOT NULL, UNIQUE INDEX uniq_pav (product_id, attribute_value_id), INDEX IDX_96CA06404584665A (product_id), INDEX IDX_96CA064065A22152 (attribute_value_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE product_faqs (id INT AUTO_INCREMENT NOT NULL, locale VARCHAR(8) NOT NULL, question VARCHAR(255) NOT NULL, answer LONGTEXT NOT NULL, position INT NOT NULL, product_id INT NOT NULL, variant_id INT DEFAULT NULL, INDEX IDX_4DC9C444584665A (product_id), INDEX IDX_4DC9C443B69A9AF (variant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE product_translations (id INT AUTO_INCREMENT NOT NULL, locale VARCHAR(8) NOT NULL, name VARCHAR(255) NOT NULL, subtitle VARCHAR(255) DEFAULT NULL, description_short LONGTEXT DEFAULT NULL, description LONGTEXT DEFAULT NULL, meta_keywords LONGTEXT DEFAULT NULL, meta_description VARCHAR(255) DEFAULT NULL, content_source VARCHAR(255) DEFAULT \'template\' NOT NULL, locked TINYINT NOT NULL, product_id INT NOT NULL, UNIQUE INDEX uniq_product_locale (product_id, locale), INDEX IDX_4B13F8EC4584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE product_variants (id INT AUTO_INCREMENT NOT NULL, legacy_id INT DEFAULT NULL, color VARCHAR(60) NOT NULL, color_hex VARCHAR(12) DEFAULT NULL, position INT NOT NULL, published TINYINT NOT NULL, product_id INT NOT NULL, INDEX idx_variant_legacy (legacy_id), INDEX IDX_782839764584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE products (id INT AUTO_INCREMENT NOT NULL, legacy_id INT DEFAULT NULL, sku VARCHAR(64) DEFAULT NULL, ean VARCHAR(32) DEFAULT NULL, manufacturer VARCHAR(120) DEFAULT NULL, product_type VARCHAR(60) DEFAULT NULL, print_technology VARCHAR(60) DEFAULT NULL, gender VARCHAR(20) DEFAULT NULL, material VARCHAR(120) DEFAULT NULL, weight_gsm INT DEFAULT NULL, published TINYINT NOT NULL, published_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX idx_product_legacy (legacy_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE redirects (id INT AUTO_INCREMENT NOT NULL, source_path VARCHAR(500) NOT NULL, target VARCHAR(500) NOT NULL, code INT DEFAULT 301 NOT NULL, hits INT NOT NULL, last_hit_at DATETIME DEFAULT NULL, origin VARCHAR(40) DEFAULT NULL, store_id INT NOT NULL, UNIQUE INDEX uniq_redirect_source (store_id, source_path), INDEX IDX_B7713AD5B092A811 (store_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE settings (id INT AUTO_INCREMENT NOT NULL, skey VARCHAR(120) NOT NULL, value JSON DEFAULT NULL, store_id INT DEFAULT NULL, UNIQUE INDEX uniq_setting (store_id, skey), INDEX IDX_E545A0C5B092A811 (store_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE shipping_methods (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(40) NOT NULL, carrier VARCHAR(255) NOT NULL, labels JSON NOT NULL, price_cod INT NOT NULL, price_transfer INT NOT NULL, has_pickup_points TINYINT NOT NULL, enabled TINYINT NOT NULL, position INT NOT NULL, store_id INT NOT NULL, INDEX IDX_B0AAF1ADB092A811 (store_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE stock (id INT AUTO_INCREMENT NOT NULL, quantity INT NOT NULL, reserved INT NOT NULL, restock_at DATETIME DEFAULT NULL, store_id INT NOT NULL, variant_size_id INT NOT NULL, UNIQUE INDEX uniq_stock (store_id, variant_size_id), INDEX IDX_4B365660B092A811 (store_id), INDEX IDX_4B36566026288561 (variant_size_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE store_domains (id INT AUTO_INCREMENT NOT NULL, host VARCHAR(160) NOT NULL, redirect_to_primary TINYINT NOT NULL, store_id INT NOT NULL, UNIQUE INDEX UNIQ_95739E7ACF2713FD (host), INDEX IDX_95739E7AB092A811 (store_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE stores (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(16) NOT NULL, name VARCHAR(120) NOT NULL, primary_host VARCHAR(120) NOT NULL, locales JSON NOT NULL, default_locale VARCHAR(8) NOT NULL, currency VARCHAR(8) NOT NULL, theme VARCHAR(40) NOT NULL, contact_email VARCHAR(160) NOT NULL, contact_phone VARCHAR(40) NOT NULL, status VARCHAR(255) DEFAULT \'active\' NOT NULL, legal_entity_id INT NOT NULL, partner_store_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_D5907CCC77153098 (code), INDEX IDX_D5907CCC6DEC420C (legal_entity_id), INDEX IDX_D5907CCCD3532EAB (partner_store_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE url_aliases (id INT AUTO_INCREMENT NOT NULL, locale VARCHAR(8) NOT NULL, path VARCHAR(500) NOT NULL, target_type VARCHAR(255) NOT NULL, target_id INT NOT NULL, canonical TINYINT NOT NULL, store_id INT NOT NULL, INDEX idx_alias_target (target_type, target_id), UNIQUE INDEX uniq_alias_path (store_id, locale, path), INDEX IDX_E261ED65B092A811 (store_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE variant_media (id INT AUTO_INCREMENT NOT NULL, path VARCHAR(500) NOT NULL, type VARCHAR(20) DEFAULT \'image\' NOT NULL, alt VARCHAR(255) DEFAULT NULL, position INT NOT NULL, variant_id INT NOT NULL, INDEX IDX_EAA63E513B69A9AF (variant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE variant_sizes (id INT AUTO_INCREMENT NOT NULL, size VARCHAR(20) NOT NULL, sku VARCHAR(64) DEFAULT NULL, ean VARCHAR(32) DEFAULT NULL, position INT NOT NULL, variant_id INT NOT NULL, INDEX IDX_361418343B69A9AF (variant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE variant_translations (id INT AUTO_INCREMENT NOT NULL, locale VARCHAR(8) NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) DEFAULT NULL, description_extended LONGTEXT DEFAULT NULL, meta_description VARCHAR(255) DEFAULT NULL, content_source VARCHAR(255) DEFAULT \'template\' NOT NULL, locked TINYINT NOT NULL, variant_id INT NOT NULL, UNIQUE INDEX uniq_variant_locale (variant_id, locale), INDEX IDX_7E8AE86E3B69A9AF (variant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE admin_user_stores ADD CONSTRAINT FK_2DD82EF8A76ED395 FOREIGN KEY (user_id) REFERENCES admin_users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE admin_user_stores ADD CONSTRAINT FK_2DD82EF8B092A811 FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE attribute_values ADD CONSTRAINT FK_184662BCB6E62EFA FOREIGN KEY (attribute_id) REFERENCES attributes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE categories ADD CONSTRAINT FK_3AF34668B092A811 FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE categories ADD CONSTRAINT FK_3AF34668727ACA70 FOREIGN KEY (parent_id) REFERENCES categories (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE category_products ADD CONSTRAINT FK_4C0DE2112469DE2 FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE category_products ADD CONSTRAINT FK_4C0DE214584665A FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE category_translations ADD CONSTRAINT FK_1C60F91512469DE2 FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE customer_addresses ADD CONSTRAINT FK_C4378D0C9395C3F3 FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE customer_consents ADD CONSTRAINT FK_2A1F8E489395C3F3 FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE customer_stores ADD CONSTRAINT FK_6455A09D9395C3F3 FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE customer_stores ADD CONSTRAINT FK_6455A09DB092A811 FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE invoices ADD CONSTRAINT FK_6A2F2F955278319C FOREIGN KEY (series_id) REFERENCES invoice_series (id)');
        $this->addSql('ALTER TABLE invoices ADD CONSTRAINT FK_6A2F2F958D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE invoices ADD CONSTRAINT FK_6A2F2F952ADD6D8C FOREIGN KEY (supplier_id) REFERENCES legal_entities (id)');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_62809DB08D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_62809DB04584665A FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_62809DB03B69A9AF FOREIGN KEY (variant_id) REFERENCES product_variants (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_62809DB026288561 FOREIGN KEY (variant_size_id) REFERENCES variant_sizes (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE order_status_history ADD CONSTRAINT FK_471AD77E8D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEEB092A811 FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE9395C3F3 FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE page_translations ADD CONSTRAINT FK_78AB76C9C4663E4 FOREIGN KEY (page_id) REFERENCES pages (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE pages ADD CONSTRAINT FK_2074E575B092A811 FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE pages ADD CONSTRAINT FK_2074E575727ACA70 FOREIGN KEY (parent_id) REFERENCES pages (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE payment_methods ADD CONSTRAINT FK_4FABF983B092A811 FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE prices ADD CONSTRAINT FK_E4CB6D59B092A811 FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE prices ADD CONSTRAINT FK_E4CB6D594584665A FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE prices ADD CONSTRAINT FK_E4CB6D593B69A9AF FOREIGN KEY (variant_id) REFERENCES product_variants (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_attribute_values ADD CONSTRAINT FK_96CA06404584665A FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_attribute_values ADD CONSTRAINT FK_96CA064065A22152 FOREIGN KEY (attribute_value_id) REFERENCES attribute_values (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_faqs ADD CONSTRAINT FK_4DC9C444584665A FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_faqs ADD CONSTRAINT FK_4DC9C443B69A9AF FOREIGN KEY (variant_id) REFERENCES product_variants (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_translations ADD CONSTRAINT FK_4B13F8EC4584665A FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_variants ADD CONSTRAINT FK_782839764584665A FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE redirects ADD CONSTRAINT FK_B7713AD5B092A811 FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE settings ADD CONSTRAINT FK_E545A0C5B092A811 FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE shipping_methods ADD CONSTRAINT FK_B0AAF1ADB092A811 FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stock ADD CONSTRAINT FK_4B365660B092A811 FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stock ADD CONSTRAINT FK_4B36566026288561 FOREIGN KEY (variant_size_id) REFERENCES variant_sizes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE store_domains ADD CONSTRAINT FK_95739E7AB092A811 FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stores ADD CONSTRAINT FK_D5907CCC6DEC420C FOREIGN KEY (legal_entity_id) REFERENCES legal_entities (id)');
        $this->addSql('ALTER TABLE stores ADD CONSTRAINT FK_D5907CCCD3532EAB FOREIGN KEY (partner_store_id) REFERENCES stores (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE url_aliases ADD CONSTRAINT FK_E261ED65B092A811 FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE variant_media ADD CONSTRAINT FK_EAA63E513B69A9AF FOREIGN KEY (variant_id) REFERENCES product_variants (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE variant_sizes ADD CONSTRAINT FK_361418343B69A9AF FOREIGN KEY (variant_id) REFERENCES product_variants (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE variant_translations ADD CONSTRAINT FK_7E8AE86E3B69A9AF FOREIGN KEY (variant_id) REFERENCES product_variants (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE admin_user_stores DROP FOREIGN KEY FK_2DD82EF8A76ED395');
        $this->addSql('ALTER TABLE admin_user_stores DROP FOREIGN KEY FK_2DD82EF8B092A811');
        $this->addSql('ALTER TABLE attribute_values DROP FOREIGN KEY FK_184662BCB6E62EFA');
        $this->addSql('ALTER TABLE categories DROP FOREIGN KEY FK_3AF34668B092A811');
        $this->addSql('ALTER TABLE categories DROP FOREIGN KEY FK_3AF34668727ACA70');
        $this->addSql('ALTER TABLE category_products DROP FOREIGN KEY FK_4C0DE2112469DE2');
        $this->addSql('ALTER TABLE category_products DROP FOREIGN KEY FK_4C0DE214584665A');
        $this->addSql('ALTER TABLE category_translations DROP FOREIGN KEY FK_1C60F91512469DE2');
        $this->addSql('ALTER TABLE customer_addresses DROP FOREIGN KEY FK_C4378D0C9395C3F3');
        $this->addSql('ALTER TABLE customer_consents DROP FOREIGN KEY FK_2A1F8E489395C3F3');
        $this->addSql('ALTER TABLE customer_stores DROP FOREIGN KEY FK_6455A09D9395C3F3');
        $this->addSql('ALTER TABLE customer_stores DROP FOREIGN KEY FK_6455A09DB092A811');
        $this->addSql('ALTER TABLE invoices DROP FOREIGN KEY FK_6A2F2F955278319C');
        $this->addSql('ALTER TABLE invoices DROP FOREIGN KEY FK_6A2F2F958D9F6D38');
        $this->addSql('ALTER TABLE invoices DROP FOREIGN KEY FK_6A2F2F952ADD6D8C');
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY FK_62809DB08D9F6D38');
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY FK_62809DB04584665A');
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY FK_62809DB03B69A9AF');
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY FK_62809DB026288561');
        $this->addSql('ALTER TABLE order_status_history DROP FOREIGN KEY FK_471AD77E8D9F6D38');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEEB092A811');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE9395C3F3');
        $this->addSql('ALTER TABLE page_translations DROP FOREIGN KEY FK_78AB76C9C4663E4');
        $this->addSql('ALTER TABLE pages DROP FOREIGN KEY FK_2074E575B092A811');
        $this->addSql('ALTER TABLE pages DROP FOREIGN KEY FK_2074E575727ACA70');
        $this->addSql('ALTER TABLE payment_methods DROP FOREIGN KEY FK_4FABF983B092A811');
        $this->addSql('ALTER TABLE prices DROP FOREIGN KEY FK_E4CB6D59B092A811');
        $this->addSql('ALTER TABLE prices DROP FOREIGN KEY FK_E4CB6D594584665A');
        $this->addSql('ALTER TABLE prices DROP FOREIGN KEY FK_E4CB6D593B69A9AF');
        $this->addSql('ALTER TABLE product_attribute_values DROP FOREIGN KEY FK_96CA06404584665A');
        $this->addSql('ALTER TABLE product_attribute_values DROP FOREIGN KEY FK_96CA064065A22152');
        $this->addSql('ALTER TABLE product_faqs DROP FOREIGN KEY FK_4DC9C444584665A');
        $this->addSql('ALTER TABLE product_faqs DROP FOREIGN KEY FK_4DC9C443B69A9AF');
        $this->addSql('ALTER TABLE product_translations DROP FOREIGN KEY FK_4B13F8EC4584665A');
        $this->addSql('ALTER TABLE product_variants DROP FOREIGN KEY FK_782839764584665A');
        $this->addSql('ALTER TABLE redirects DROP FOREIGN KEY FK_B7713AD5B092A811');
        $this->addSql('ALTER TABLE settings DROP FOREIGN KEY FK_E545A0C5B092A811');
        $this->addSql('ALTER TABLE shipping_methods DROP FOREIGN KEY FK_B0AAF1ADB092A811');
        $this->addSql('ALTER TABLE stock DROP FOREIGN KEY FK_4B365660B092A811');
        $this->addSql('ALTER TABLE stock DROP FOREIGN KEY FK_4B36566026288561');
        $this->addSql('ALTER TABLE store_domains DROP FOREIGN KEY FK_95739E7AB092A811');
        $this->addSql('ALTER TABLE stores DROP FOREIGN KEY FK_D5907CCC6DEC420C');
        $this->addSql('ALTER TABLE stores DROP FOREIGN KEY FK_D5907CCCD3532EAB');
        $this->addSql('ALTER TABLE url_aliases DROP FOREIGN KEY FK_E261ED65B092A811');
        $this->addSql('ALTER TABLE variant_media DROP FOREIGN KEY FK_EAA63E513B69A9AF');
        $this->addSql('ALTER TABLE variant_sizes DROP FOREIGN KEY FK_361418343B69A9AF');
        $this->addSql('ALTER TABLE variant_translations DROP FOREIGN KEY FK_7E8AE86E3B69A9AF');
        $this->addSql('DROP TABLE admin_user_stores');
        $this->addSql('DROP TABLE admin_users');
        $this->addSql('DROP TABLE attribute_values');
        $this->addSql('DROP TABLE attributes');
        $this->addSql('DROP TABLE audit_log');
        $this->addSql('DROP TABLE categories');
        $this->addSql('DROP TABLE category_products');
        $this->addSql('DROP TABLE category_translations');
        $this->addSql('DROP TABLE customer_addresses');
        $this->addSql('DROP TABLE customer_consents');
        $this->addSql('DROP TABLE customer_stores');
        $this->addSql('DROP TABLE customers');
        $this->addSql('DROP TABLE id_map');
        $this->addSql('DROP TABLE import_batches');
        $this->addSql('DROP TABLE invoice_series');
        $this->addSql('DROP TABLE invoices');
        $this->addSql('DROP TABLE legal_entities');
        $this->addSql('DROP TABLE media');
        $this->addSql('DROP TABLE order_items');
        $this->addSql('DROP TABLE order_status_history');
        $this->addSql('DROP TABLE orders');
        $this->addSql('DROP TABLE page_translations');
        $this->addSql('DROP TABLE pages');
        $this->addSql('DROP TABLE payment_methods');
        $this->addSql('DROP TABLE prices');
        $this->addSql('DROP TABLE product_attribute_values');
        $this->addSql('DROP TABLE product_faqs');
        $this->addSql('DROP TABLE product_translations');
        $this->addSql('DROP TABLE product_variants');
        $this->addSql('DROP TABLE products');
        $this->addSql('DROP TABLE redirects');
        $this->addSql('DROP TABLE settings');
        $this->addSql('DROP TABLE shipping_methods');
        $this->addSql('DROP TABLE stock');
        $this->addSql('DROP TABLE store_domains');
        $this->addSql('DROP TABLE stores');
        $this->addSql('DROP TABLE url_aliases');
        $this->addSql('DROP TABLE variant_media');
        $this->addSql('DROP TABLE variant_sizes');
        $this->addSql('DROP TABLE variant_translations');
    }
}
