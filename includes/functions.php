<?php
/**
 * Eterea Gestionale
 * Funzioni comuni
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions_security.php';

// Imposta timezone italiano
date_default_timezone_set('Europe/Rome');

// Debug mode - imposta a true per abilitare logging dettagliato
define('DEBUG_MODE', true);

/**
 * Log di debug
 * @param string $message Messaggio da loggare
 * @param mixed $data Dati aggiuntivi (opzionale)
 * @param string $type Tipo di log (info, error, warning, sql)
 */
function debugLog($message, $data = null, $type = 'info') {
    if (!DEBUG_MODE) return;
    
    $prefix = '[DEBUG ' . strtoupper($type) . '] ';
    $logMessage = $prefix . $message;
    
    if ($data !== null) {
        if (is_array($data) || is_object($data)) {
            $logMessage .= ' | DATA: ' . json_encode($data);
        } else {
            $logMessage .= ' | DATA: ' . $data;
        }
    }
    
    $logMessage .= ' | URL: ' . ($_SERVER['REQUEST_URI'] ?? 'CLI');
    $logMessage .= ' | METHOD: ' . ($_SERVER['REQUEST_METHOD'] ?? 'CLI');
    $logMessage .= ' | TIME: ' . date('Y-m-d H:i:s');
    
    error_log($logMessage);
}

/**
 * Log delle query SQL
 * @param string $sql Query SQL
 * @param array $params Parametri (opzionale)
 */
function debugQuery($sql, $params = []) {
    if (!DEBUG_MODE) return;
    
    $message = 'SQL: ' . $sql;
    if (!empty($params)) {
        $message .= ' | PARAMS: ' . json_encode($params);
    }
    
    debugLog($message, null, 'sql');
}

/**
 * Log delle variabili POST/GET
 */
function debugRequest() {
    if (!DEBUG_MODE) return;
    
    $data = [
        'GET' => $_GET,
        'POST' => $_POST,
        'FILES' => !empty($_FILES) ? 'FILES_PRESENT' : 'NO_FILES',
        'SESSION' => isset($_SESSION) ? ['user_id' => $_SESSION['user_id'] ?? 'not_set'] : 'NO_SESSION'
    ];
    
    debugLog('REQUEST DATA', $data, 'info');
}

/**
 * Verifica se l'utente è autenticato
 */
function isLoggedIn() {
    if (session_status() === PHP_SESSION_NONE) {
        // Se headers già inviati, non possiamo settare ini
        if (!headers_sent()) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
        }
        session_start();
    }
    
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Verifica se l'utente è admin
 */
function isAdmin() {
    // Per ora consideriamo admin l'utente con ID specifico
    // Puoi modificare questa logica in base alle tue esigenze
    if (!isLoggedIn()) {
        return false;
    }
    
    // Admin sono: Lorenzo (ucwurog3xr8tf) e eventuali altri
    $adminIds = ['ucwurog3xr8tf'];
    return in_array($_SESSION['user_id'], $adminIds);
}

/**
 * Verifica che l'utente sia autenticato (per API)
 */
function requireAuth() {
    if (!isLoggedIn()) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Non autenticato']);
        exit;
    }
}

/**
 * Ottiene l'ID dell'utente corrente
 */
function currentUserId() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return $_SESSION['user_id'] ?? '';
}

/**
 * Genera un ID univoco (formato simile a quelli esistenti)
 */
/**
 * Genera un ID breve per task/progetti (formato: txxx o pxxx)
 */
function generateEntityId($prefix) {
    return $prefix . substr(md5(uniqid(mt_rand(), true)), 0, 10);
}

/**
 * Sanitizza output per prevenire XSS
 */
function e($text) {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitizza input stringa
 */
function sanitizeInput($input) {
    return trim(htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8'));
}

/**
 * Genera token CSRF
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica token CSRF
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Risponde con JSON
 */
function jsonResponse($success, $data = null, $message = '') {
    header('Content-Type: application/json');
    echo json_encode(array(
        'success' => $success,
        'data' => $data,
        'message' => $message
    ));
    exit;
}

/**
 * Log azione nella timeline
 */
function logTimeline($utenteId, $azione, $entitaTipo, $entitaId, $dettagli = '') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO timeline (utente_id, azione, entita_tipo, entita_id, dettagli, auto_delete_date)
            VALUES (?, ?, ?, ?, ?, DATE_ADD(CURDATE(), INTERVAL 15 DAY))
        ");
        $stmt->execute([$utenteId, $azione, $entitaTipo, $entitaId, $dettagli]);
    } catch (PDOException $e) {
        error_log("Errore log timeline: " . $e->getMessage());
    }
}

/**
 * Pulizia timeline (può essere chiamata da cron o ad ogni login)
 */
function pulisciTimeline() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM timeline WHERE auto_delete_date < CURDATE()");
        $stmt->execute();
    } catch (PDOException $e) {
        error_log("Errore pulizia timeline: " . $e->getMessage());
    }
}

/**
 * Calcola la distribuzione economica di un progetto
 * 
 * @param float $totale Importo totale del progetto
 * @param array $partecipantiIds Array degli ID dei partecipanti attivi
 * @param bool $includiCassa Includi cassa aziendale
 * @param bool $includiPassivo Includi quota membri non attivi
 * @param bool $distribuzioneUniforme Dividi equamente tra i partecipanti
 * @param array $configCustom Configurazione percentuali personalizzata (opzionale)
 * @return array Distribuzione calcolata
 */
function calcolaDistribuzione($totale, $partecipantiIds, $includiCassa = true, $includiPassivo = false, $distribuzioneUniforme = false, $configCustom = null) {
    $count = count($partecipantiIds);
    $distribuzione = [];
    
    // Tutti gli utenti possibili
    $tuttiUtenti = ['ucwurog3xr8tf', 'ukl9ipuolsebn', 'u3ghz4f2lnpkx'];
    
    // Se c'è una configurazione personalizzata, usa quella
    if ($configCustom && is_array($configCustom)) {
        foreach ($configCustom as $id => $percentuale) {
            if ($percentuale > 0) {
                $importo = round($totale * ($percentuale / 100), 2);
                $tipo = ($id === 'cassa') ? 'cassa' : 'attivo';
                $distribuzione[$id] = [
                    'importo' => $importo,
                    'percentuale' => $percentuale,
                    'tipo' => $tipo
                ];
            }
        }
        return $distribuzione;
    }
    
    // Se distribuzione uniforme: divide equamente tra i partecipanti (meno cassa se inclusa)
    if ($distribuzioneUniforme) {
        $cassaPercent = $includiCassa ? 0.10 : 0;
        $importoRimanente = $totale * (1 - $cassaPercent);
        $quotaPerPersona = round($importoRimanente / $count, 2);
        $percentualePerPersona = round(((1 - $cassaPercent) / $count) * 100);
        
        foreach ($partecipantiIds as $uid) {
            $distribuzione[$uid] = [
                'importo' => $quotaPerPersona,
                'percentuale' => $percentualePerPersona,
                'tipo' => 'attivo'
            ];
        }
        
        if ($includiCassa) {
            $distribuzione['cassa'] = [
                'importo' => round($totale * 0.10, 2),
                'percentuale' => 10,
                'tipo' => 'cassa'
            ];
        }
        
        return $distribuzione;
    }
    
    // Calcola percentuali disponibili (logica originale)
    $cassaPercent = $includiCassa ? 0.10 : 0;
    $basePercent = 1 - $cassaPercent; // 0.90 o 1.00
    
    $numPassivi = 3 - $count;
    $percentualePassivi = $includiPassivo ? ($numPassivi * 0.10) : 0;
    $percentualePerAttivi = $basePercent - $percentualePassivi;
    
    switch($count) {
        case 3:
            // Tutti e 3 attivi: dividi la percentuale disponibile
            $share = $percentualePerAttivi / 3;
            foreach($partecipantiIds as $uid) {
                $distribuzione[$uid] = [
                    'importo' => round($totale * $share, 2),
                    'percentuale' => round($share * 100),
                    'tipo' => 'attivo'
                ];
            }
            if ($includiCassa) {
                $distribuzione['cassa'] = [
                    'importo' => round($totale * 0.10, 2),
                    'percentuale' => 10,
                    'tipo' => 'cassa'
                ];
            }
            break;
            
        case 2:
            // 2 attivi: dividi la percentuale disponibile
            $share = $percentualePerAttivi / 2;
            foreach($partecipantiIds as $uid) {
                $distribuzione[$uid] = [
                    'importo' => round($totale * $share, 2),
                    'percentuale' => round($share * 100),
                    'tipo' => 'attivo'
                ];
            }
            // Aggiungi passivo solo se richiesto
            if ($includiPassivo) {
                $inattivi = array_diff($tuttiUtenti, $partecipantiIds);
                foreach($inattivi as $uid) {
                    $distribuzione[$uid] = [
                        'importo' => round($totale * 0.10, 2),
                        'percentuale' => 10,
                        'tipo' => 'passivo'
                    ];
                }
            }
            if ($includiCassa) {
                $distribuzione['cassa'] = [
                    'importo' => round($totale * 0.10, 2),
                    'percentuale' => 10,
                    'tipo' => 'cassa'
                ];
            }
            break;
            
        case 1:
            // 1 attivo: prende tutta la percentuale disponibile
            $share = $percentualePerAttivi;
            $distribuzione[$partecipantiIds[0]] = [
                'importo' => round($totale * $share, 2),
                'percentuale' => round($share * 100),
                'tipo' => 'attivo'
            ];
            // Aggiungi passivi solo se richiesto
            if ($includiPassivo) {
                $inattivi = array_diff($tuttiUtenti, $partecipantiIds);
                foreach($inattivi as $uid) {
                    $distribuzione[$uid] = [
                        'importo' => round($totale * 0.10, 2),
                        'percentuale' => 10,
                        'tipo' => 'passivo'
                    ];
                }
            }
            if ($includiCassa) {
                $distribuzione['cassa'] = [
                    'importo' => round($totale * 0.10, 2),
                    'percentuale' => 10,
                    'tipo' => 'cassa'
                ];
            }
            break;
            
        default:
            // Caso non previsto: tutto in cassa
            if ($includiCassa) {
                $distribuzione['cassa'] = [
                    'importo' => $totale,
                    'percentuale' => 100,
                    'tipo' => 'cassa'
                ];
            }
    }
    
    return $distribuzione;
}

/**
 * Esegue la distribuzione economica e salva le transazioni
 */
function eseguiDistribuzione($progettoId, $totale, $partecipantiIds, $includiCassa = true, $includiPassivo = false, $distribuzioneUniforme = false, $utentiEsclusi = []) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Recupera configurazione pagamento mensile del progetto
        $stmt = $pdo->prepare("SELECT pagamento_mensile, distribuzione_mensile_config FROM progetti WHERE id = ?");
        $stmt->execute([$progettoId]);
        $progetto = $stmt->fetch();
        
        $configCustom = null;
        if ($progetto && $progetto['pagamento_mensile'] && $progetto['distribuzione_mensile_config']) {
            $configCustom = json_decode($progetto['distribuzione_mensile_config'], true);
        }
        
        // Calcola distribuzione (con/senza quota passiva, uniforme o standard, o custom)
        $distribuzione = calcolaDistribuzione($totale, $partecipantiIds, $includiCassa, $includiPassivo, $distribuzioneUniforme, $configCustom);
        
        // Salva transazioni
        foreach ($distribuzione as $id => $dati) {
            if ($id === 'cassa') {
                // Transazione cassa
                $stmt = $pdo->prepare("
                    INSERT INTO transazioni_economiche 
                    (progetto_id, tipo, importo, percentuale, descrizione)
                    VALUES (?, 'cassa', ?, ?, 'Contributo cassa aziendale')
                ");
                $stmt->execute([$progettoId, $dati['importo'], $dati['percentuale']]);
            } else {
                // Transazione wallet utente
                $stmt = $pdo->prepare("
                    INSERT INTO transazioni_economiche 
                    (progetto_id, tipo, utente_id, importo, percentuale, descrizione)
                    VALUES (?, 'wallet', ?, ?, ?, ?)
                ");
                $descrizione = $dati['tipo'] === 'attivo' 
                    ? 'Compenso progetto (attivo)' 
                    : 'Compenso progetto (passivo)';
                $stmt->execute([$progettoId, $id, $dati['importo'], $dati['percentuale'], $descrizione]);
                
                // Aggiorna saldo wallet
                $stmt = $pdo->prepare("
                    UPDATE utenti SET wallet_saldo = wallet_saldo + ? WHERE id = ?
                ");
                $stmt->execute([$dati['importo'], $id]);
            }
        }
        
        // Segna distribuzione come effettuata
        $stmt = $pdo->prepare("
            UPDATE progetti SET distribuzione_effettuata = TRUE WHERE id = ?
        ");
        $stmt->execute([$progettoId]);
        
        $pdo->commit();
        return true;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Errore distribuzione: " . $e->getMessage());
        return false;
    }
}

/**
 * Formatta importo in euro
 */
function formatCurrency($amount) {
    return '€ ' . number_format($amount, 2, ',', '.');
}

/**
 * Formatta data in formato italiano
 */
function formatDate($date, $format = 'd/m/Y') {
    if (!$date) return '-';
    return date($format, strtotime($date));
}

/**
 * Formatta datetime in formato italiano
 */
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    if (!$datetime) return '-';
    
    // Gestione timezone: converte da UTC (database) a Europe/Rome
    try {
        $dt = new DateTime($datetime, new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone('Europe/Rome'));
        return $dt->format($format);
    } catch (Exception $e) {
        // Fallback alla funzione originale
        return date($format, strtotime($datetime));
    }
}

/**
 * Formatta bytes in formato leggibile
 */
function formatBytes($bytes, $precision = 1) {
    if ($bytes === 0 || $bytes === null) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Verifica i pagamenti mensili in scadenza e crea notifiche
 * Da chiamare nel dashboard o in un cron job
 * 
 * @param int $giorniAnticipo Giorni di anticipo per l'avviso (default: 3)
 * @return array Progetti con pagamenti in scadenza
 */
function verificaScadenzePagamentiMensili($giorniAnticipo = 3) {
    global $pdo;
    
    try {
        $oggi = new DateTime();
        $giornoCorrente = intval($oggi->format('j'));
        $meseCorrente = intval($oggi->format('n'));
        $annoCorrente = intval($oggi->format('Y'));
        
        // Recupera tutti i progetti con pagamento mensile attivo
        $stmt = $pdo->query("
            SELECT id, titolo, prezzo_mensile, giorno_scadenza_mensile, 
                   data_inizio_pagamento, last_pagamento_mensile_notified
            FROM progetti 
            WHERE pagamento_mensile = 1 
              AND stato_progetto NOT IN ('archiviato', 'completato')
        ");
        $progetti = $stmt->fetchAll();
        
        $scadenze = [];
        
        foreach ($progetti as $progetto) {
            $giornoScadenza = intval($progetto['giorno_scadenza_mensile']);
            $dataInizio = new DateTime($progetto['data_inizio_pagamento']);
            $lastNotified = $progetto['last_pagamento_mensile_notified'] ? new DateTime($progetto['last_pagamento_mensile_notified']) : null;
            
            // Calcola la prossima scadenza
            if ($giornoScadenza >= $giornoCorrente) {
                // Scadenza nel mese corrente
                $prossimaScadenza = new DateTime("$annoCorrente-$meseCorrente-$giornoScadenza");
            } else {
                // Scadenza nel prossimo mese
                $prossimaScadenza = new DateTime("$annoCorrente-$meseCorrente-$giornoScadenza");
                $prossimaScadenza->modify('+1 month');
            }
            
            // Verifica se è ora di notificare (entro $giorniAnticipo giorni)
            $diffGiorni = $oggi->diff($prossimaScadenza)->days;
            $daNotificare = $prossimaScadenza >= $oggi && $diffGiorni <= $giorniAnticipo;
            
            // Evita notifiche duplicate nello stesso mese
            if ($lastNotified) {
                $lastNotifiedMonth = intval($lastNotified->format('n'));
                $lastNotifiedYear = intval($lastNotified->format('Y'));
                $scadenzaMonth = intval($prossimaScadenza->format('n'));
                $scadenzaYear = intval($prossimaScadenza->format('Y'));
                
                if ($lastNotifiedMonth === $scadenzaMonth && $lastNotifiedYear === $scadenzaYear) {
                    $daNotificare = false;
                }
            }
            
            if ($daNotificare) {
                $scadenze[] = [
                    'progetto_id' => $progetto['id'],
                    'titolo' => $progetto['titolo'],
                    'prezzo' => $progetto['prezzo_mensile'],
                    'giorno_scadenza' => $giornoScadenza,
                    'data_scadenza' => $prossimaScadenza->format('Y-m-d'),
                    'giorni_mancanti' => $diffGiorni
                ];
                
                // Crea notifica
                creaNotifica(
                    'scadenza_pagamento_mensile',
                    'Scadenza Pagamento Mensile',
                    "Il progetto '{$progetto['titolo']}' ha un pagamento di € {$progetto['prezzo_mensile']} in scadenza il {$prossimaScadenza->format('d/m/Y')}",
                    'progetto',
                    $progetto['id'],
                    null // Tutti gli utenti
                );
                
                // Aggiorna timestamp ultima notifica
                $stmtUpdate = $pdo->prepare("
                    UPDATE progetti SET last_pagamento_mensile_notified = NOW() 
                    WHERE id = ?
                ");
                $stmtUpdate->execute([$progetto['id']]);
            }
        }
        
        return $scadenze;
        
    } catch (PDOException $e) {
        error_log("Errore verifica scadenze pagamenti mensili: " . $e->getMessage());
        return [];
    }
}

/**
 * Esegue i pagamenti mensili automatici per i progetti configurati
 * Da chiamare nel dashboard o in un cron job
 * 
 * @return array Pagamenti eseguiti
 */
function eseguiPagamentiMensiliAutomatici() {
    // Funzione disabilitata - pagamento mensile rimosso
    return [];
}

/**
 * Carica file uploadato in modo sicuro
 * 
 * @param array $file Array $_FILES['campo']
 * @param string $destinationDir Directory di destinazione (relativa a UPLOAD_PATH)
 * @param array $allowedTypes Tipi MIME consentiti
 * @param int $maxSize Dimensione massima in bytes
 * @return array|false ['path' => ..., 'filename' => ..., 'size' => ...] o false
 */
/**
 * DEPRECATO: Usare uploadFileSecure() invece
 * @deprecated
 */
function uploadFile($file, $destinationDir, $allowedTypes, $maxSize) {
    // Wrapper per retrocompatibilità, usa la versione sicura
    return uploadFileSecure($file, $destinationDir, $allowedTypes, $maxSize, false);
}

/**
 * Verifica se una data è scaduta o in scadenza
 * 
 * @param string $scadenza Data di scadenza
 * @param int $giorniAnticipo Giorni di anticipo per considerare "in scadenza"
 * @return string 'scaduto', 'in_scadenza', 'ok'
 */
function checkScadenza($scadenza, $giorniAnticipo = 1) {
    $oggi = new DateTime();
    $oggi->setTime(0, 0, 0);
    
    $dataScadenza = new DateTime($scadenza);
    $dataScadenza->setTime(0, 0, 0);
    
    $diff = $oggi->diff($dataScadenza);
    $giorni = (int)$diff->format('%r%a');
    
    if ($giorni < 0) {
        return 'scaduto';
    } elseif ($giorni <= $giorniAnticipo) {
        return 'in_scadenza';
    }
    return 'ok';
}

/**
 * Crea un appuntamento automatico per una task
 */
function creaAppuntamentoTask($taskId, $progettoId, $titolo, $scadenza, $assegnatoA) {
    global $pdo;
    
    try {
        $id = generateEntityId('a');
        $stmt = $pdo->prepare("
            INSERT INTO appuntamenti 
            (id, titolo, tipo, data_inizio, progetto_id, task_id, utente_id, created_by)
            VALUES (?, ?, 'scadenza_task', ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$id, 'Scadenza: ' . $titolo, $scadenza, $progettoId, $taskId, $assegnatoA, $assegnatoA]);
    } catch (PDOException $e) {
        error_log("Errore creazione appuntamento task: " . $e->getMessage());
    }
}

/**
 * Crea notifica nel database per tutti gli utenti o un utente specifico
 */
function creaNotifica($tipo, $titolo, $messaggio, $entitaTipo = null, $entitaId = null, $creatoDa = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifiche (tipo, titolo, messaggio, entita_tipo, entita_id, creato_da)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$tipo, $titolo, $messaggio, $entitaTipo, $entitaId, $creatoDa]);
    } catch (PDOException $e) {
        error_log("Errore creazione notifica: " . $e->getMessage());
    }
}

/**
 * Ottieni statistiche dashboard per un utente
 */
function getDashboardStats($utenteId) {
    global $pdo;
    
    $stats = [
        'cassa_aziendale' => 0,
        'miei_crediti' => 0,
        'progetti_attivi' => 0,
        'task_oggi' => [],
        'prossime_scadenze' => [],
        'timeline' => []
    ];
    
    try {
        // Carica giorni preavviso scadenze dalle impostazioni
        $stmt = $pdo->query("SELECT valore FROM impostazioni WHERE chiave = 'giorni_preavviso_scadenze'");
        $giorniPreavviso = intval($stmt->fetchColumn() ?: '1');
        $stats['giorni_preavviso'] = $giorniPreavviso;
        
        // Cassa aziendale (somma di tutte le transazioni cassa)
        $stmt = $pdo->query("
            SELECT COALESCE(SUM(importo), 0) as totale 
            FROM transazioni_economiche 
            WHERE tipo = 'cassa'
        ");
        $stats['cassa_aziendale'] = (float)$stmt->fetchColumn();
        
        // Miei crediti (wallet)
        $stmt = $pdo->prepare("
            SELECT wallet_saldo FROM utenti WHERE id = ?
        ");
        $stmt->execute([$utenteId]);
        $stats['miei_crediti'] = (float)$stmt->fetchColumn();
        
        // Progetti attivi (dove l'utente è partecipante)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM progetti 
            WHERE stato_progetto IN ('da_iniziare', 'in_corso', 'completato')
            AND JSON_SEARCH(partecipanti, 'one', ?) IS NOT NULL
        ");
        $stmt->execute([$utenteId]);
        $stats['progetti_attivi'] = (int)$stmt->fetchColumn();
        
        // Task per oggi
        $stmt = $pdo->prepare("
            SELECT t.*, p.titolo as progetto_titolo 
            FROM task t
            JOIN progetti p ON t.progetto_id = p.id
            WHERE t.assegnato_a = ? 
            AND DATE(t.scadenza) = CURDATE()
            AND t.stato != 'completato'
            ORDER BY t.priorita DESC
        ");
        $stmt->execute([$utenteId]);
        $stats['task_oggi'] = $stmt->fetchAll();
        
        // Prossime scadenze (progetti che scadono nei prossimi X giorni configurati)
        $stmt = $pdo->prepare("
            SELECT p.*, c.ragione_sociale as cliente_nome
            FROM progetti p
            LEFT JOIN clienti c ON p.cliente_id = c.id
            WHERE DATE(p.data_consegna_prevista) >= CURDATE()
            AND DATE(p.data_consegna_prevista) <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
            AND p.stato_progetto NOT IN ('consegnato', 'archiviato', 'annullato')
            ORDER BY p.data_consegna_prevista ASC
            LIMIT 5
        ");
        $stmt->execute([$giorniPreavviso]);
        $stats['prossime_scadenze'] = $stmt->fetchAll();
        
        // Timeline recente
        $stmt = $pdo->query("
            SELECT tl.*, u.nome as utente_nome
            FROM timeline tl
            LEFT JOIN utenti u ON tl.utente_id = u.id
            ORDER BY tl.timestamp DESC
            LIMIT 10
        ");
        $stats['timeline'] = $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log("Errore stats dashboard: " . $e->getMessage());
    }
    
    return $stats;
}
