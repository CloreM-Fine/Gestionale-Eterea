<?php
/**
 * Eterea Gestionale
 * Funzioni comuni
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions_security.php';

// Imposta timezone italiano
date_default_timezone_set('Europe/Rome');

/**
 * Verifica che l'utente sia autenticato (per API)
 */
function requireAuth(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        session_start();
    }
    
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Non autenticato']);
        exit;
    }
}

/**
 * Ottiene l'ID dell'utente corrente
 */
function currentUserId(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return $_SESSION['user_id'] ?? '';
}

/**
 * Genera un ID univoco (formato simile a quelli esistenti)
 */
function generateId(): string {
    return 'u' . substr(md5(uniqid(mt_rand(), true)), 0, 12) . substr(uniqid(), -1);
}

/**
 * Genera un ID breve per task/progetti (formato: txxx o pxxx)
 */
function generateEntityId(string $prefix): string {
    return $prefix . substr(md5(uniqid(mt_rand(), true)), 0, 10);
}

/**
 * Sanitizza output per prevenire XSS
 */
function e(string $text): string {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitizza input stringa
 */
function sanitizeInput(string $input): string {
    return trim(htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8'));
}

/**
 * Genera token CSRF
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica token CSRF
 */
function verifyCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Risponde con JSON
 */
function jsonResponse(bool $success, $data = null, string $message = ''): void {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ]);
    exit;
}

/**
 * Log azione nella timeline
 */
function logTimeline(string $utenteId, string $azione, string $entitaTipo, string $entitaId, string $dettagli = ''): void {
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
function pulisciTimeline(): void {
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
 * @return array Distribuzione calcolata
 */
function calcolaDistribuzione(float $totale, array $partecipantiIds, bool $includiCassa = true, bool $includiPassivo = false): array {
    $count = count($partecipantiIds);
    $distribuzione = [];
    
    // Tutti gli utenti possibili
    $tuttiUtenti = ['ucwurog3xr8tf', 'ukl9ipuolsebn', 'u3ghz4f2lnpkx'];
    
    // Percentuale cassa e rimanente
    $cassaPercent = $includiCassa ? 0.10 : 0;
    $remainingPercent = 1 - $cassaPercent;
    
    // Quota passiva da ridistribuire se non includiamo i passivi
    $numPassivi = 3 - $count;
    $quotaPassivaRidistribuibile = !$includiPassivo ? $numPassivi * 0.10 : 0;
    
    switch($count) {
        case 3:
            // Tutti e 3 attivi: dividi equamente
            $share = $remainingPercent / 3;
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
            // 2 attivi: 40% ciascuno + quota passiva ridistribuita (se non includiPassivo)
            $activeShare = $includiCassa ? 0.40 : 0.45;
            $bonusShare = $includiPassivo ? 0 : 0.10;
            $share = $activeShare + $bonusShare;
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
            // 1 attivo: 70% + quota passiva ridistribuita (se non includiPassivo)
            $activeShare = $includiCassa ? 0.70 : 0.80;
            $bonusShare = $includiPassivo ? 0 : 0.20;
            $share = $activeShare + $bonusShare;
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
function eseguiDistribuzione(string $progettoId, float $totale, array $partecipantiIds, bool $includiCassa = true, bool $includiPassivo = false, array $utentiEsclusi = []): bool {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Calcola distribuzione (con/senza quota passiva)
        $distribuzione = calcolaDistribuzione($totale, $partecipantiIds, $includiCassa, $includiPassivo);
        
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
function formatCurrency(float $amount): string {
    return '€ ' . number_format($amount, 2, ',', '.');
}

/**
 * Formatta data in formato italiano
 */
function formatDate(?string $date, string $format = 'd/m/Y'): string {
    if (!$date) return '-';
    return date($format, strtotime($date));
}

/**
 * Formatta datetime in formato italiano
 */
function formatDateTime(?string $datetime, string $format = 'd/m/Y H:i'): string {
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
 * Ottiene il nome di un utente dal suo ID
 */
function getUserName(string $userId): string {
    return USERS[$userId]['nome'] ?? 'Utente sconosciuto';
}

/**
 * Ottiene il colore di un utente dal suo ID
 */
function getUserColor(string $userId): string {
    return USERS[$userId]['colore'] ?? '#3B82F6';
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
function uploadFile(array $file, string $destinationDir, array $allowedTypes, int $maxSize): array|false {
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
function checkScadenza(string $scadenza, int $giorniAnticipo = 1): string {
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
function creaAppuntamentoTask(string $taskId, string $progettoId, string $titolo, string $scadenza, string $assegnatoA): void {
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
function creaNotifica(string $tipo, string $titolo, string $messaggio, ?string $entitaTipo = null, ?string $entitaId = null, ?string $creatoDa = null): void {
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
function getDashboardStats(string $utenteId): array {
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
        
        // Prossime scadenze (progetti che scadono nei prossimi 7 giorni)
        $stmt = $pdo->prepare("
            SELECT p.*, c.ragione_sociale as cliente_nome
            FROM progetti p
            LEFT JOIN clienti c ON p.cliente_id = c.id
            WHERE DATE(p.data_consegna_prevista) >= CURDATE()
            AND DATE(p.data_consegna_prevista) <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            AND p.stato_progetto NOT IN ('consegnato', 'archiviato', 'annullato')
            ORDER BY p.data_consegna_prevista ASC
            LIMIT 5
        ");
        $stmt->execute();
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
