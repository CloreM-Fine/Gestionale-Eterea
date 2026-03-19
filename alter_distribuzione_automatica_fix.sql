-- Fix: Crea tabella senza foreign key (per evitare problemi di compatibilità charset)

-- 1. Aggiungi campo distribuzione_automatica (se non già fatto)
ALTER TABLE progetti 
ADD COLUMN IF NOT EXISTS distribuzione_automatica TINYINT(1) DEFAULT 0 AFTER pagamento_mensile;

-- 2. Crea tabella per tracciare i pagamenti mensili eseguiti (SENZA foreign key)
CREATE TABLE IF NOT EXISTS progetti_pagamenti_mensili (
    id INT AUTO_INCREMENT PRIMARY KEY,
    progetto_id VARCHAR(20) NOT NULL,
    mese INT NOT NULL,
    anno INT NOT NULL,
    importo DECIMAL(10,2) NOT NULL,
    data_esecuzione DATETIME DEFAULT CURRENT_TIMESTAMP,
    distribuzione_config JSON NULL,
    INDEX idx_progetto (progetto_id),
    INDEX idx_mese_anno (mese, anno)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
