<?php
/**
 * ETEREA STUDIO - Gestionale
 * Setup: Tabelle Mail
 * Crea le tabelle necessarie per la gestione email
 * 
 * @copyright 2024-2025 Eterea Studio
 * @license MIT
 */

require_once __DIR__ . '/../includes/functions.php';

echo "🔧 Setup tabelle Mail\n";
echo "====================\n\n";

try {
    global $pdo;
    
    // Tabella account email
    echo "Creazione tabella mail_accounts... ";
    $pdo->exec("CREATE TABLE IF NOT EXISTS mail_accounts (
        id VARCHAR(20) PRIMARY KEY,
        utente_id VARCHAR(20) NOT NULL,
        email VARCHAR(255) NOT NULL,
        nome_visualizzato VARCHAR(255),
        imap_server VARCHAR(255),
        imap_port INT DEFAULT 993,
        imap_ssl TINYINT DEFAULT 1,
        imap_username VARCHAR(255),
        imap_password TEXT,
        smtp_server VARCHAR(255),
        smtp_port INT DEFAULT 587,
        smtp_ssl TINYINT DEFAULT 1,
        smtp_username VARCHAR(255),
        smtp_password TEXT,
        is_default TINYINT DEFAULT 0,
        attivo TINYINT DEFAULT 1,
        ultima_sincronizzazione DATETIME,
        creato_il TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        aggiornato_il TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_utente (utente_id),
        INDEX idx_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ OK\n";
    
    // Tabella messaggi email
    echo "Creazione tabella mail_messages... ";
    $pdo->exec("CREATE TABLE IF NOT EXISTS mail_messages (
        id VARCHAR(20) PRIMARY KEY,
        account_id VARCHAR(20) NOT NULL,
        message_id VARCHAR(500),
        parent_id VARCHAR(20),
        cliente_id VARCHAR(20),
        progetto_id VARCHAR(20),
        cartella VARCHAR(50) DEFAULT 'inbox',
        mittente_email VARCHAR(255),
        mittente_nome VARCHAR(255),
        destinatari TEXT,
        cc TEXT,
        bcc TEXT,
        oggetto VARCHAR(500),
        corpo_text TEXT,
        corpo_html LONGTEXT,
        data_ricezione DATETIME,
        data_invio DATETIME,
        is_letta TINYINT DEFAULT 0,
        is_inviata TINYINT DEFAULT 0,
        is_bozza TINYINT DEFAULT 0,
        is_importante TINYINT DEFAULT 0,
        is_spam TINYINT DEFAULT 0,
        is_cestinata TINYINT DEFAULT 0,
        has_allegati TINYINT DEFAULT 0,
        allegati_count INT DEFAULT 0,
        uid_imap VARCHAR(255),
        size_bytes INT,
        creato_il TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_account_cartella (account_id, cartella),
        INDEX idx_cliente (cliente_id),
        INDEX idx_progetto (progetto_id),
        INDEX idx_data (data_ricezione),
        INDEX idx_message_id (message_id(255))
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ OK\n";
    
    // Tabella allegati email
    echo "Creazione tabella mail_attachments... ";
    $pdo->exec("CREATE TABLE IF NOT EXISTS mail_attachments (
        id VARCHAR(20) PRIMARY KEY,
        message_id VARCHAR(20) NOT NULL,
        filename VARCHAR(255),
        original_filename VARCHAR(255),
        mime_type VARCHAR(100),
        file_size INT,
        file_path VARCHAR(500),
        is_inline TINYINT DEFAULT 0,
        content_id VARCHAR(255),
        creato_il TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_message (message_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ OK\n";
    
    // Crea directory per allegati email se non esiste
    $uploadDir = __DIR__ . '/../assets/uploads/mail_attachments/';
    if (!is_dir($uploadDir)) {
        echo "Creazione directory mail_attachments... ";
        mkdir($uploadDir, 0755, true);
        echo "✅ OK\n";
    }
    
    echo "\n✅ Setup completato con successo!\n";
    echo "Le tabelle mail_accounts, mail_messages e mail_attachments sono pronte.\n";
    
} catch (Exception $e) {
    echo "\n❌ ERRORE: " . $e->getMessage() . "\n";
    exit(1);
}
