-- Aggiunta campo per distribuzione automatica mensile
ALTER TABLE progetti 
ADD COLUMN distribuzione_automatica TINYINT(1) DEFAULT 0 AFTER pagamento_mensile;

-- Tabella per tracciare i pagamenti mensili effettuati
CREATE TABLE IF NOT EXISTS progetti_pagamenti_mensili (
    id INT AUTO_INCREMENT PRIMARY KEY,
    progetto_id VARCHAR(20) NOT NULL,
    mese INT NOT NULL,
    anno INT NOT NULL,
    importo DECIMAL(10,2) NOT NULL,
    data_esecuzione DATETIME DEFAULT CURRENT_TIMESTAMP,
    distribuzione_config JSON NULL,
    INDEX idx_progetto (progetto_id),
    INDEX idx_mese_anno (mese, anno),
    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
