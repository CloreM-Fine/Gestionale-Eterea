-- =====================================================
-- ETEREA GESTIONALE - Blog Clienti
-- Tabella per contenuti caricati dai clienti
-- =====================================================

CREATE TABLE IF NOT EXISTS `cliente_contenuti` (
    `id` VARCHAR(20) NOT NULL,
    `cliente_id` VARCHAR(20) NOT NULL,
    `progetto_id` VARCHAR(20) DEFAULT NULL,
    `token` VARCHAR(64) NOT NULL,
    `titolo` VARCHAR(255) DEFAULT NULL,
    `autore` VARCHAR(255) DEFAULT NULL,
    `testo` TEXT,
    `immagini` JSON,
    `stato` ENUM('attivo', 'archiviato', 'eliminato') DEFAULT 'attivo',
    `letto` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` VARCHAR(20) DEFAULT NULL,
    `data_caricamento` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `token` (`token`),
    KEY `cliente_id` (`cliente_id`),
    KEY `progetto_id` (`progetto_id`),
    KEY `stato` (`stato`),
    KEY `letto` (`letto`),
    KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Aggiungi colonna autore se tabella esiste già (ALTER TABLE per aggiornamenti)
SET @dbname = DATABASE();
SET @tablename = 'cliente_contenuti';
SET @columnname = 'autore';
SET @preparedStatement = (SELECT IF(
    (
        SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = @dbname
        AND TABLE_NAME = @tablename
        AND COLUMN_NAME = @columnname
    ) = 0,
    CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' VARCHAR(255) DEFAULT NULL AFTER titolo;'),
    'SELECT 1;'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Aggiungi colonna progetto_id se non esiste
SET @columnname = 'progetto_id';
SET @preparedStatement = (SELECT IF(
    (
        SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = @dbname
        AND TABLE_NAME = @tablename
        AND COLUMN_NAME = @columnname
    ) = 0,
    CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' VARCHAR(20) DEFAULT NULL AFTER cliente_id;'),
    'SELECT 1;'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Aggiungi colonna data_caricamento se non esiste
SET @columnname = 'data_caricamento';
SET @preparedStatement = (SELECT IF(
    (
        SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = @dbname
        AND TABLE_NAME = @tablename
        AND COLUMN_NAME = @columnname
    ) = 0,
    CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' TIMESTAMP NULL DEFAULT NULL AFTER updated_at;'),
    'SELECT 1;'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Tabella per i link generati (opzionale, per tracciare i link creati)
CREATE TABLE IF NOT EXISTS `cliente_link` (
    `id` VARCHAR(20) NOT NULL,
    `cliente_id` VARCHAR(20) NOT NULL,
    `token` VARCHAR(64) NOT NULL,
    `note` TEXT,
    `usato` TINYINT(1) DEFAULT 0,
    `data_utilizzo` DATETIME DEFAULT NULL,
    `expires_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `created_by` VARCHAR(20) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `token` (`token`),
    KEY `cliente_id` (`cliente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
