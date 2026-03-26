<?php
/**
 * Eterea Gestionale - Setup Piano Editoriale
 * Script di installazione/aggiornamento database
 * 
 * Da eseguire via browser o CLI
 */

require_once __DIR__ . '/../includes/functions.php';

echo "<pre>";
echo "=== SETUP PIANO EDITORIALE ===\n\n";

try {
    // 1. Verifica e crea colonna gestione_social
    echo "1. Controllo colonna 'gestione_social' in tabella 'progetti'...\n";
    
    $stmt = $pdo->query("
        SELECT COUNT(*) as col_exists 
        FROM information_schema.columns 
        WHERE table_schema = DATABASE() 
        AND table_name = 'progetti' 
        AND column_name = 'gestione_social'
    ");
    $result = $stmt->fetch();
    
    if ($result['col_exists'] == 0) {
        echo "   -> Colonna NON esistente, creazione in corso...\n";
        $pdo->exec("
            ALTER TABLE progetti 
            ADD COLUMN gestione_social TINYINT(1) NOT NULL DEFAULT 0 
            COMMENT 'Indica se il progetto include gestione social (1=si, 0=no)'
        ");
        echo "   -> Colonna 'gestione_social' CREATA con successo!\n\n";
    } else {
        echo "   -> Colonna già esistente, SKIP.\n\n";
    }
    
    // 2. Crea tabella piano_editoriale
    echo "2. Controllo tabella 'piano_editoriale'...\n";
    
    $stmt = $pdo->query("
        SELECT COUNT(*) as table_exists 
        FROM information_schema.tables 
        WHERE table_schema = DATABASE() 
        AND table_name = 'piano_editoriale'
    ");
    $result = $stmt->fetch();
    
    if ($result['table_exists'] == 0) {
        echo "   -> Tabella NON esistente, creazione in corso...\n";
        $pdo->exec("CREATE TABLE piano_editoriale (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            progetto_id VARCHAR(20) NOT NULL COMMENT 'ID del progetto collegato',
            cliente_id VARCHAR(20) DEFAULT NULL COMMENT 'ID del cliente (per reference)',
            titolo VARCHAR(255) NOT NULL COMMENT 'Titolo del post/contenuto',
            descrizione TEXT COMMENT 'Testo/descrizione del post',
            piattaforma ENUM('instagram', 'facebook', 'tiktok', 'linkedin', 'twitter', 'youtube', 'pinterest', 'altro') NOT NULL DEFAULT 'instagram',
            tipologia ENUM('feed', 'stories', 'reels', 'carousel', 'video', 'live', 'sponsored', 'altro') NOT NULL DEFAULT 'feed',
            stato ENUM('bozza', 'in_revisione', 'approvato', 'programmato', 'pubblicato', 'archiviato') NOT NULL DEFAULT 'bozza',
            data_prevista DATE NOT NULL COMMENT 'Data prevista per la pubblicazione',
            ora_prevista TIME DEFAULT NULL COMMENT 'Ora prevista per la pubblicazione',
            data_pubblicazione DATETIME DEFAULT NULL COMMENT 'Data effettiva di pubblicazione',
            creato_da VARCHAR(20) NOT NULL COMMENT 'ID utente che ha creato il post',
            assegnato_a VARCHAR(20) DEFAULT NULL COMMENT 'ID utente assegnato',
            approvato_da VARCHAR(20) DEFAULT NULL COMMENT 'ID utente che ha approvato il post',
            impressions INT UNSIGNED DEFAULT NULL COMMENT 'Visualizzazioni',
            reach INT UNSIGNED DEFAULT NULL COMMENT 'Persone raggiunte',
            engagement INT UNSIGNED DEFAULT NULL COMMENT 'Interazioni totali',
            likes INT UNSIGNED DEFAULT NULL COMMENT 'Mi piace',
            comments INT UNSIGNED DEFAULT NULL COMMENT 'Commenti',
            shares INT UNSIGNED DEFAULT NULL COMMENT 'Condivisioni',
            saves INT UNSIGNED DEFAULT NULL COMMENT 'Salvataggi',
            clicks INT UNSIGNED DEFAULT NULL COMMENT 'Click sul link',
            note TEXT COMMENT 'Note interne sul post',
            link_esterno VARCHAR(500) DEFAULT NULL COMMENT 'Link a post pubblicato',
            hashtag TEXT COMMENT 'Hashtag da utilizzare',
            menzioni TEXT COMMENT 'Account da menzionare',
            is_sponsored TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Indica se e un post sponsorizzato',
            budget_sponsorizzato DECIMAL(10,2) DEFAULT NULL COMMENT 'Budget per sponsorizzazione',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
            FOREIGN KEY (cliente_id) REFERENCES clienti(id) ON DELETE SET NULL,
            FOREIGN KEY (creato_da) REFERENCES utenti(id) ON DELETE RESTRICT,
            FOREIGN KEY (assegnato_a) REFERENCES utenti(id) ON DELETE SET NULL,
            FOREIGN KEY (approvato_da) REFERENCES utenti(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='Tabella per la gestione del piano editoriale social media'");
        echo "   -> Tabella 'piano_editoriale' CREATA con successo!\n\n";
    } else {
        echo "   -> Tabella già esistente, SKIP.\n\n";
    }
    
    // 3. Crea tabella piano_editoriale_contenuti
    echo "3. Controllo tabella 'piano_editoriale_contenuti'...\n";
    
    $stmt = $pdo->query("
        SELECT COUNT(*) as table_exists 
        FROM information_schema.tables 
        WHERE table_schema = DATABASE() 
        AND table_name = 'piano_editoriale_contenuti'
    ");
    $result = $stmt->fetch();
    
    if ($result['table_exists'] == 0) {
        echo "   -> Tabella NON esistente, creazione in corso...\n";
        $pdo->exec("CREATE TABLE piano_editoriale_contenuti (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_id INT UNSIGNED NOT NULL COMMENT 'ID del post in piano_editoriale',
            filename VARCHAR(255) NOT NULL COMMENT 'Nome del file',
            file_path VARCHAR(500) NOT NULL COMMENT 'Percorso relativo del file',
            file_type VARCHAR(100) NOT NULL COMMENT 'Tipo MIME del file',
            file_size INT UNSIGNED NOT NULL COMMENT 'Dimensione in bytes',
            tipo ENUM('immagine', 'video', 'audio', 'documento', 'altro') NOT NULL DEFAULT 'immagine',
            ordine TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Ordine di visualizzazione',
            didascalia VARCHAR(500) DEFAULT NULL COMMENT 'Didascalia specifica',
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            uploaded_by VARCHAR(20) NOT NULL COMMENT 'ID utente che ha caricato il file',
            FOREIGN KEY (post_id) REFERENCES piano_editoriale(id) ON DELETE CASCADE,
            FOREIGN KEY (uploaded_by) REFERENCES utenti(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='Contenuti multimediali allegati ai post del piano editoriale'");
        echo "   -> Tabella 'piano_editoriale_contenuti' CREATA con successo!\n\n";
    } else {
        echo "   -> Tabella già esistente, SKIP.\n\n";
    }
    
    // 4. Crea tabella piano_editoriale_approvazioni
    echo "4. Controllo tabella 'piano_editoriale_approvazioni'...\n";
    
    $stmt = $pdo->query("
        SELECT COUNT(*) as table_exists 
        FROM information_schema.tables 
        WHERE table_schema = DATABASE() 
        AND table_name = 'piano_editoriale_approvazioni'
    ");
    $result = $stmt->fetch();
    
    if ($result['table_exists'] == 0) {
        echo "   -> Tabella NON esistente, creazione in corso...\n";
        $pdo->exec("CREATE TABLE piano_editoriale_approvazioni (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_id INT UNSIGNED NOT NULL COMMENT 'ID del post',
            utente_id VARCHAR(20) NOT NULL COMMENT 'ID utente che ha approvato/rifiutato',
            azione ENUM('approvato', 'rifiutato', 'richiesta_modifica') NOT NULL,
            commento TEXT DEFAULT NULL COMMENT 'Commento sulla decisione',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (post_id) REFERENCES piano_editoriale(id) ON DELETE CASCADE,
            FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='Storico approvazioni post piano editoriale'");
        echo "   -> Tabella 'piano_editoriale_approvazioni' CREATA con successo!\n\n";
    } else {
        echo "   -> Tabella già esistente, SKIP.\n\n";
    }
    
    // 5. Crea tabella piano_editoriale_template
    echo "5. Controllo tabella 'piano_editoriale_template'...\n";
    
    $stmt = $pdo->query("
        SELECT COUNT(*) as table_exists 
        FROM information_schema.tables 
        WHERE table_schema = DATABASE() 
        AND table_name = 'piano_editoriale_template'
    ");
    $result = $stmt->fetch();
    
    if ($result['table_exists'] == 0) {
        echo "   -> Tabella NON esistente, creazione in corso...\n";
        $pdo->exec("CREATE TABLE piano_editoriale_template (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(255) NOT NULL COMMENT 'Nome del template',
            descrizione TEXT COMMENT 'Descrizione del template',
            titolo_template VARCHAR(255) DEFAULT NULL COMMENT 'Titolo base',
            testo_template TEXT COMMENT 'Testo base',
            piattaforma ENUM('instagram', 'facebook', 'tiktok', 'linkedin', 'twitter', 'youtube', 'pinterest', 'altro', 'multi') NOT NULL DEFAULT 'multi',
            tipologia ENUM('feed', 'stories', 'reels', 'carousel', 'video', 'live', 'sponsored', 'altro') NOT NULL DEFAULT 'feed',
            hashtag_default TEXT COMMENT 'Hashtag di default',
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_by VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES utenti(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='Template riutilizzabili per creazione rapida post'");
        echo "   -> Tabella 'piano_editoriale_template' CREATA con successo!\n\n";
    } else {
        echo "   -> Tabella già esistente, SKIP.\n\n";
    }
    
    echo "=== SETUP COMPLETATO CON SUCCESSO! ===\n";
    echo "Ora puoi usare la sezione 'Piano Editoriale' nel gestionale.\n";
    echo "\n<a href='../piano_editoriale.php'>Vai al Piano Editoriale</a>";
    
} catch (PDOException $e) {
    echo "\n!!! ERRORE !!!\n";
    echo "Messaggio: " . $e->getMessage() . "\n";
    echo "Codice: " . $e->getCode() . "\n";
}

echo "</pre>";
