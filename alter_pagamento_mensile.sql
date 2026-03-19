-- Aggiunta campi per la gestione del pagamento mensile

-- Campi per il pagamento mensile
ALTER TABLE progetti 
ADD COLUMN pagamento_mensile TINYINT(1) DEFAULT 0 AFTER note,
ADD COLUMN prezzo_mensile DECIMAL(10,2) DEFAULT 0 AFTER pagamento_mensile,
ADD COLUMN giorno_scadenza_mensile INT DEFAULT 1 AFTER prezzo_mensile,
ADD COLUMN data_inizio_pagamento DATE NULL AFTER giorno_scadenza_mensile,
ADD COLUMN distribuzione_mensile_config JSON NULL AFTER data_inizio_pagamento,
ADD COLUMN last_pagamento_mensile_notified DATETIME NULL AFTER distribuzione_mensile_config;

-- Indice per ricerca rapida
CREATE INDEX idx_pagamento_mensile ON progetti(pagamento_mensile, stato_progetto);
