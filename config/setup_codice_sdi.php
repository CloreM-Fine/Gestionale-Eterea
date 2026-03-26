<?php
/**
 * Script per aggiungere la colonna codice_sdi alla tabella clienti
 */

require_once __DIR__ . '/../includes/functions.php';

echo "<pre>";
echo "=== AGGIORNAMENTO DATABASE - CODICE SDI ===\n\n";

try {
    // Verifica se la colonna esiste
    $stmt = $pdo->query("
        SELECT COUNT(*) as col_exists 
        FROM information_schema.columns 
        WHERE table_schema = DATABASE() 
        AND table_name = 'clienti' 
        AND column_name = 'codice_sdi'
    ");
    $result = $stmt->fetch();
    
    if ($result['col_exists'] == 0) {
        echo "-> Colonna 'codice_sdi' non esistente, creazione in corso...\n";
        $pdo->exec("
            ALTER TABLE clienti 
            ADD COLUMN codice_sdi VARCHAR(7) DEFAULT NULL 
            COMMENT 'Codice SDI per fatturazione elettronica' 
            AFTER piva_cf
        ");
        echo "-> Colonna 'codice_sdi' CREATA con successo!\n";
    } else {
        echo "-> Colonna 'codice_sdi' già esistente, SKIP.\n";
    }
    
    echo "\n=== AGGIORNAMENTO COMPLETATO ===";
    
} catch (PDOException $e) {
    echo "\n!!! ERRORE !!!\n";
    echo "Messaggio: " . $e->getMessage() . "\n";
}

echo "</pre>";
