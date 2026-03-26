-- =====================================================
-- ETEREA GESTIONALE - SETUP PIANO EDITORIALE
-- Database: MySQL/MariaDB
-- =====================================================

-- -----------------------------------------------------
-- 1. AGGIUNGI COLONNA gestione_social ALLA TABELLA progetti
-- -----------------------------------------------------
-- Verifica se la colonna esiste già
SET @dbname = DATABASE();
SET @tablename = 'progetti';
SET @columnname = 'gestione_social';

SET @sql = CONCAT(
    'SELECT COUNT(*) INTO @col_exists FROM information_schema.columns ',
    'WHERE table_schema = ''', @dbname, ''' ',
    'AND table_name = ''', @tablename, ''' ',
    'AND column_name = ''', @columnname, ''''
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Aggiungi colonna solo se non esiste
SET @sql = CONCAT(
    'ALTER TABLE progetti ',
    'ADD COLUMN gestione_social TINYINT(1) NOT NULL DEFAULT 0 ',
    'COMMENT ''Indica se il progetto include gestione social (1=si, 0=no)'''
);
SET @sql = IF(@col_exists = 0, @sql, 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------
-- 2. TABELLA piano_editoriale
-- Contiene i post pianificati per i progetti social
-- -----------------------------------------------------
CREATE TABLE piano_editoriale (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    progetto_id VARCHAR(20) NOT NULL COMMENT 'ID del progetto collegato',
    cliente_id VARCHAR(20) DEFAULT NULL COMMENT 'ID del cliente (per reference)',
    
    -- Contenuto del post
    titolo VARCHAR(255) NOT NULL COMMENT 'Titolo del post/contenuto',
    descrizione TEXT COMMENT 'Testo/descrizione del post',
    
    -- Piattaforma social
    piattaforma ENUM('instagram', 'facebook', 'tiktok', 'linkedin', 'twitter', 'youtube', 'pinterest', 'altro') NOT NULL DEFAULT 'instagram',
    
    -- Tipologia contenuto
    tipologia ENUM('feed', 'stories', 'reels', 'carousel', 'video', 'live', 'sponsored', 'altro') NOT NULL DEFAULT 'feed',
    
    -- Stato del post
    stato ENUM('bozza', 'in_revisione', 'approvato', 'programmato', 'pubblicato', 'archiviato') NOT NULL DEFAULT 'bozza',
    
    -- Date importanti
    data_prevista DATE NOT NULL COMMENT 'Data prevista per la pubblicazione',
    ora_prevista TIME DEFAULT NULL COMMENT 'Ora prevista per la pubblicazione',
    data_pubblicazione DATETIME DEFAULT NULL COMMENT 'Data effettiva di pubblicazione',
    
    -- Assegnazione e responsabilità
    creato_da VARCHAR(20) NOT NULL COMMENT 'ID utente che ha creato il post',
    assegnato_a VARCHAR(20) DEFAULT NULL COMMENT 'ID utente assegnato (copywriter, designer, ecc.)',
    approvato_da VARCHAR(20) DEFAULT NULL COMMENT 'ID utente che ha approvato il post',
    
    -- Metriche e performance (opzionali, popolate dopo pubblicazione)
    impressions INT UNSIGNED DEFAULT NULL COMMENT 'Visualizzazioni',
    reach INT UNSIGNED DEFAULT NULL COMMENT 'Persone raggiunte',
    engagement INT UNSIGNED DEFAULT NULL COMMENT 'Interazioni totali',
    likes INT UNSIGNED DEFAULT NULL COMMENT 'Mi piace',
    comments INT UNSIGNED DEFAULT NULL COMMENT 'Commenti',
    shares INT UNSIGNED DEFAULT NULL COMMENT 'Condivisioni',
    saves INT UNSIGNED DEFAULT NULL COMMENT 'Salvataggi',
    clicks INT UNSIGNED DEFAULT NULL COMMENT 'Click sul link',
    
    -- Note e link
    note TEXT COMMENT 'Note interne sul post',
    link_esterno VARCHAR(500) DEFAULT NULL COMMENT 'Link a post pubblicato o risorsa esterna',
    
    -- Hashtag e menzioni
    hashtag TEXT COMMENT 'Hashtag da utilizzare (separati da virgola)',
    menzioni TEXT COMMENT 'Account da menzionare (separati da virgola)',
    
    -- Campi per campagne sponsorizzate
    is_sponsored TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Indica se è un post sponsorizzato',
    budget_sponsorizzato DECIMAL(10,2) DEFAULT NULL COMMENT 'Budget per sponsorizzazione',
    
    -- Timestamp
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indici per performance
    INDEX idx_progetto_id (progetto_id),
    INDEX idx_cliente_id (cliente_id),
    INDEX idx_stato (stato),
    INDEX idx_data_prevista (data_prevista),
    INDEX idx_piattaforma (piattaforma),
    INDEX idx_creato_da (creato_da),
    INDEX idx_assegnato_a (assegnato_a),
    INDEX idx_progetto_data (progetto_id, data_prevista),
    INDEX idx_stato_data (stato, data_prevista),
    
    -- Vincoli di integrità
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
    FOREIGN KEY (cliente_id) REFERENCES clienti(id) ON DELETE SET NULL,
    FOREIGN KEY (creato_da) REFERENCES utenti(id) ON DELETE RESTRICT,
    FOREIGN KEY (assegnato_a) REFERENCES utenti(id) ON DELETE SET NULL,
    FOREIGN KEY (approvato_da) REFERENCES utenti(id) ON DELETE SET NULL
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tabella per la gestione del piano editoriale social media';

-- -----------------------------------------------------
-- 3. TABELLA piano_editoriale_contenuti
-- Contenuti multimediali allegati ai post (immagini, video)
-- -----------------------------------------------------
CREATE TABLE piano_editoriale_contenuti (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL COMMENT 'ID del post in piano_editoriale',
    
    -- File
    filename VARCHAR(255) NOT NULL COMMENT 'Nome del file',
    file_path VARCHAR(500) NOT NULL COMMENT 'Percorso relativo del file',
    file_type VARCHAR(100) NOT NULL COMMENT 'Tipo MIME del file',
    file_size INT UNSIGNED NOT NULL COMMENT 'Dimensione in bytes',
    
    -- Tipologia contenuto
    tipo ENUM('immagine', 'video', 'audio', 'documento', 'altro') NOT NULL DEFAULT 'immagine',
    
    -- Ordine e didascalia
    ordine TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Ordine di visualizzazione',
    didascalia VARCHAR(500) DEFAULT NULL COMMENT 'Didascalia specifica per questo contenuto',
    
    -- Timestamp
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    uploaded_by VARCHAR(20) NOT NULL COMMENT 'ID utente che ha caricato il file',
    
    -- Indici
    INDEX idx_post_id (post_id),
    INDEX idx_tipo (tipo),
    INDEX idx_ordine (ordine),
    
    -- Vincoli
    FOREIGN KEY (post_id) REFERENCES piano_editoriale(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES utenti(id) ON DELETE RESTRICT
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Contenuti multimediali allegati ai post del piano editoriale';

-- -----------------------------------------------------
-- 4. TABELLA piano_editoriale_approvazioni
-- Traccia lo storico delle approvazioni/rifiuti
-- -----------------------------------------------------
CREATE TABLE piano_editoriale_approvazioni (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL COMMENT 'ID del post',
    utente_id VARCHAR(20) NOT NULL COMMENT 'ID utente che ha approvato/rifiutato',
    azione ENUM('approvato', 'rifiutato', 'richiesta_modifica') NOT NULL,
    commento TEXT DEFAULT NULL COMMENT 'Commento sulla decisione',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Indici
    INDEX idx_post_id (post_id),
    INDEX idx_utente_id (utente_id),
    
    -- Vincoli
    FOREIGN KEY (post_id) REFERENCES piano_editoriale(id) ON DELETE CASCADE,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE RESTRICT
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Storico approvazioni post piano editoriale';

-- -----------------------------------------------------
-- 5. TABELLA piano_editoriale_template
-- Template riutilizzabili per post ricorrenti
-- -----------------------------------------------------
CREATE TABLE piano_editoriale_template (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL COMMENT 'Nome del template',
    descrizione TEXT COMMENT 'Descrizione del template',
    
    -- Contenuto base
    titolo_template VARCHAR(255) DEFAULT NULL COMMENT 'Titolo base (con placeholder)',
    testo_template TEXT COMMENT 'Testo base (con placeholder)',
    
    -- Configurazione
    piattaforma ENUM('instagram', 'facebook', 'tiktok', 'linkedin', 'twitter', 'youtube', 'pinterest', 'altro', 'multi') NOT NULL DEFAULT 'multi',
    tipologia ENUM('feed', 'stories', 'reels', 'carousel', 'video', 'live', 'sponsored', 'altro') NOT NULL DEFAULT 'feed',
    
    -- Placeholder suggeriti
    hashtag_default TEXT COMMENT 'Hashtag di default',
    
    -- Metadati
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indici
    INDEX idx_is_active (is_active),
    INDEX idx_created_by (created_by),
    
    -- Vincoli
    FOREIGN KEY (created_by) REFERENCES utenti(id) ON DELETE RESTRICT
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Template riutilizzabili per creazione rapida post';

-- -----------------------------------------------------
-- MESSAGGIO DI CONFERMA
-- -----------------------------------------------------
SELECT 'Setup piano editoriale completato con successo!' AS messaggio;
