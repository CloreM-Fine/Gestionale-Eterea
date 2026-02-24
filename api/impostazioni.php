<?php
/**
 * Eterea Gestionale
 * API Impostazioni di sistema
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Solo admin può accedere alle impostazioni avanzate
// (opzionale - togliere il commento se vuoi limitare l'accesso)
// if ($_SESSION['user_id'] !== 'ucwurog3xr8tf') {
//     jsonResponse(false, null, 'Accesso negato');
// }

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($method) {
    case 'GET':
        if ($action === 'backup') {
            exportBackup($_GET['tipo'] ?? '');
        } elseif ($action === 'get_logo') {
            getLogo();
        } elseif ($action === 'get_dati_azienda') {
            getDatiAzienda();
        } elseif ($action === 'get_codici_ateco') {
            getCodiciAteco();
        } elseif ($action === 'get_impostazioni_tasse') {
            getImpostazioniTasse();
        } elseif ($action === 'get_impostazioni_contabilita') {
            getImpostazioniContabilita();
        } else {
            jsonResponse(false, null, 'Azione non valida');
        }
        break;
        
    case 'POST':
        if ($action === 'delete_cronologia') {
            deleteCronologia();
        } elseif ($action === 'reset_saldi') {
            resetSaldi();
        } elseif ($action === 'delete_all') {
            deleteAll($_POST['keyword'] ?? '');
        } elseif ($action === 'upload_avatar') {
            uploadAvatar();
        } elseif ($action === 'save_logo') {
            saveLogo();
        } elseif ($action === 'save_dati_azienda') {
            saveDatiAzienda();
        } elseif ($action === 'upload_logo_azienda') {
            uploadLogoAzienda();
        } elseif ($action === 'save_codice_ateco') {
            saveCodiceAteco();
        } elseif ($action === 'delete_codice_ateco') {
            deleteCodiceAteco();
        } elseif ($action === 'save_impostazioni_tasse') {
            saveImpostazioniTasse();
        } elseif ($action === 'save_impostazioni_contabilita') {
            saveImpostazioniContabilita();
        } elseif ($action === 'change_password') {
            changePassword();
        } else {
            jsonResponse(false, null, 'Azione non valida');
        }
        break;
        
    default:
        jsonResponse(false, null, 'Metodo non consentito');
}

/**
 * Elimina tutta la cronologia (timeline)
 */
function deleteCronologia(): void {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM timeline WHERE 1");
        $stmt->execute();
        
        // Reset auto increment
        $pdo->exec("ALTER TABLE timeline AUTO_INCREMENT = 1");
        
        logTimeline($_SESSION['user_id'], 'pulizia_dati', 'sistema', '', "Eliminata cronologia da impostazioni");
        
        jsonResponse(true, null, 'Cronologia eliminata con successo');
    } catch (PDOException $e) {
        error_log("Errore eliminazione cronologia: " . $e->getMessage());
        jsonResponse(false, null, 'Errore durante eliminazione');
    }
}

/**
 * Azzera i saldi di tutti gli utenti
 */
function resetSaldi(): void {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // 1. Salva i saldi precedenti per log
        $stmt = $pdo->query("SELECT id, nome, wallet_saldo FROM utenti WHERE wallet_saldo > 0");
        $utenti = $stmt->fetchAll();
        
        // 2. Azzera tutti i saldi
        $pdo->exec("UPDATE utenti SET wallet_saldo = 0");
        
        // 3. Elimina tutte le transazioni wallet
        $pdo->exec("DELETE FROM wallet_transactions");
        $pdo->exec("ALTER TABLE wallet_transactions AUTO_INCREMENT = 1");
        
        $pdo->commit();
        
        // Log
        $totale = array_sum(array_column($utenti, 'wallet_saldo'));
        logTimeline($_SESSION['user_id'], 'pulizia_dati', 'sistema', '', "Azzerati saldi per " . count($utenti) . " utenti (totale €{$totale})");
        
        jsonResponse(true, null, 'Saldi azzerati con successo');
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Errore reset saldi: " . $e->getMessage());
        jsonResponse(false, null, 'Errore durante il reset');
    }
}

/**
 * Elimina TUTTI i dati del sistema
 */
function deleteAll(string $keyword): void {
    global $pdo;
    
    if ($keyword !== 'CANCELLA TUTTO') {
        jsonResponse(false, null, 'Keyword non valida');
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Elimina in ordine corretto (dalle tabelle figlie alle padri)
        $tables = [
            'timeline',
            'wallet_transactions', 
            'tasks',
            'progetto_allegati',
            'progetti',
            'clienti',
            'eventi_calendario'
        ];
        
        foreach ($tables as $table) {
            $pdo->exec("DELETE FROM {$table}");
            $pdo->exec("ALTER TABLE {$table} AUTO_INCREMENT = 1");
        }
        
        $pdo->commit();
        
        logTimeline($_SESSION['user_id'], 'pulizia_dati', 'sistema', '', "Eliminazione completa di tutti i dati");
        
        jsonResponse(true, null, 'Tutti i dati sono stati eliminati');
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Errore eliminazione completa: " . $e->getMessage());
        jsonResponse(false, null, 'Errore durante eliminazione');
    }
}

/**
 * Esporta backup CSV
 */
function exportBackup(string $tipo): void {
    global $pdo;
    
    $filename = "backup_{$tipo}_" . date('Y-m-d_H-i-s') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    switch ($tipo) {
        case 'clienti':
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM per Excel
            fputcsv($output, ['ID', 'Nome', 'Email', 'Telefono', 'Indirizzo', 'Data Creazione']);
            $stmt = $pdo->query("SELECT * FROM clienti ORDER BY data_creazione DESC");
            while ($row = $stmt->fetch()) {
                fputcsv($output, [
                    $row['id'], $row['nome'], $row['email'], 
                    $row['telefono'], $row['indirizzo'], $row['data_creazione']
                ]);
            }
            break;
            
        case 'progetti':
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($output, ['ID', 'Nome', 'Cliente', 'Stato', 'Prezzo', 'Data Creazione']);
            $stmt = $pdo->query("
                SELECT p.*, c.nome as cliente_nome 
                FROM progetti p 
                LEFT JOIN clienti c ON p.cliente_id = c.id 
                ORDER BY p.data_creazione DESC
            ");
            while ($row = $stmt->fetch()) {
                fputcsv($output, [
                    $row['id'], $row['nome'], $row['cliente_nome'],
                    $row['stato'], $row['prezzo_totale'], $row['data_creazione']
                ]);
            }
            break;
            
        case 'finanze':
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($output, ['Data', 'Tipo', 'Importo', 'Wallet', 'Descrizione', 'Progetto']);
            $stmt = $pdo->query("
                SELECT t.*, w.nome as wallet_nome, p.nome as progetto_nome
                FROM wallet_transactions t
                JOIN wallets w ON t.wallet_id = w.id
                LEFT JOIN progetti p ON t.progetto_id = p.id
                ORDER BY t.data DESC
            ");
            while ($row = $stmt->fetch()) {
                fputcsv($output, [
                    $row['data'], $row['tipo'], $row['importo'],
                    $row['wallet_nome'], $row['descrizione'], $row['progetto_nome']
                ]);
            }
            break;
            
        case 'appuntamenti':
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($output, ['ID', 'Titolo', 'Data Inizio', 'Data Fine', 'Tipo', 'Descrizione']);
            $stmt = $pdo->query("SELECT * FROM eventi_calendario ORDER BY data_inizio DESC");
            while ($row = $stmt->fetch()) {
                fputcsv($output, [
                    $row['id'], $row['titolo'], $row['data_inizio'],
                    $row['data_fine'], $row['tipo'], $row['descrizione']
                ]);
            }
            break;
    }
    
    fclose($output);
    exit;
}

/**
 * Upload avatar utente
 */
function uploadAvatar(): void {
    if (!isset($_FILES['avatar'])) {
        jsonResponse(false, null, 'Nessun file caricato');
        return;
    }
    
    $file = $_FILES['avatar'];
    $userId = $_SESSION['user_id'];
    
    // Validazione
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        jsonResponse(false, null, 'Formato non valido. Usa JPG, PNG, GIF o WEBP');
        return;
    }
    
    if ($file['size'] > 2 * 1024 * 1024) {
        jsonResponse(false, null, 'File troppo grande (max 2MB)');
        return;
    }
    
    // Crea directory se non esiste
    $uploadDir = __DIR__ . '/../assets/uploads/avatars/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Nome file
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $userId . '_' . time() . '.' . $ext;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Aggiorna DB
        global $pdo;
        $avatarUrl = 'assets/uploads/avatars/' . $filename;
        $stmt = $pdo->prepare("UPDATE utenti SET avatar = ? WHERE id = ?");
        $stmt->execute([$avatarUrl, $userId]);
        
        jsonResponse(true, ['avatar_url' => $avatarUrl], 'Avatar aggiornato');
    } else {
        jsonResponse(false, null, 'Errore durante il salvataggio');
    }
}

/**
 * Salva il logo azienda (base64)
 */
function saveLogo(): void {
    $logoData = $_POST['logo'] ?? '';
    
    if (empty($logoData)) {
        jsonResponse(false, null, 'Nessun logo fornito');
        return;
    }
    
    // Estrai dati base64
    if (!preg_match('/^data:image\/(\w+);base64,/', $logoData, $matches)) {
        jsonResponse(false, null, 'Formato immagine non valido');
        return;
    }
    
    $imageType = $matches[1];
    $imageData = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $logoData));
    
    if ($imageData === false) {
        jsonResponse(false, null, 'Errore decodifica immagine');
        return;
    }
    
    // Crea directory
    $uploadDir = __DIR__ . '/../assets/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Salva file
    $filename = 'logo_azienda_' . time() . '.' . $imageType;
    $filepath = $uploadDir . $filename;
    
    if (file_put_contents($filepath, $imageData)) {
        // Salva path nel database
        global $pdo;
        $logoUrl = 'assets/uploads/' . $filename;
        
        $stmt = $pdo->prepare("
            INSERT INTO impostazioni (chiave, valore) 
            VALUES ('logo_azienda', ?)
            ON DUPLICATE KEY UPDATE valore = ?
        ");
        $stmt->execute([$logoUrl, $logoUrl]);
        
        jsonResponse(true, ['logo_url' => $logoUrl], 'Logo salvato con successo');
    } else {
        jsonResponse(false, null, 'Errore durante il salvataggio');
    }
}

/**
 * Ottiene il logo azienda
 */
function getLogo(): void {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT valore FROM impostazioni WHERE chiave = 'logo_azienda'");
    $stmt->execute();
    $logo = $stmt->fetchColumn();
    
    jsonResponse(true, ['logo_url' => $logo ?: null]);
}

/**
 * Ottiene i dati azienda
 */
function getDatiAzienda(): void {
    global $pdo;
    
    $chiavi = ['azienda_nome', 'azienda_indirizzo', 'azienda_email', 'azienda_telefono', 'azienda_piva', 'azienda_ateco_id'];
    $dati = [];
    
    foreach ($chiavi as $chiave) {
        $stmt = $pdo->prepare("SELECT valore FROM impostazioni WHERE chiave = ?");
        $stmt->execute([$chiave]);
        $dati[str_replace('azienda_', '', $chiave)] = $stmt->fetchColumn() ?: '';
    }
    
    // Carica anche il logo
    $stmt = $pdo->prepare("SELECT valore FROM impostazioni WHERE chiave = 'logo_azienda'");
    $stmt->execute();
    $dati['logo'] = $stmt->fetchColumn() ?: '';
    
    jsonResponse(true, $dati);
}

/**
 * Salva i dati azienda
 */
function saveDatiAzienda(): void {
    global $pdo;
    
    $campi = [
        'azienda_nome' => $_POST['nome'] ?? '',
        'azienda_indirizzo' => $_POST['indirizzo'] ?? '',
        'azienda_email' => $_POST['email'] ?? '',
        'azienda_telefono' => $_POST['telefono'] ?? '',
        'azienda_piva' => $_POST['piva'] ?? '',
        'azienda_ateco_id' => $_POST['ateco_id'] ?? ''
    ];
    
    try {
        foreach ($campi as $chiave => $valore) {
            $stmt = $pdo->prepare("
                INSERT INTO impostazioni (chiave, valore, tipo, descrizione) 
                VALUES (?, ?, 'text', ?)
                ON DUPLICATE KEY UPDATE valore = ?
            ");
            $desc = str_replace(['azienda_', '_'], ['', ' '], $chiave);
            $stmt->execute([$chiave, $valore, $desc, $valore]);
        }
        
        jsonResponse(true, null, 'Dati azienda salvati');
    } catch (PDOException $e) {
        error_log("Errore save dati azienda: " . $e->getMessage());
        jsonResponse(false, null, 'Errore durante il salvataggio');
    }
}

/**
 * Upload logo azienda da file
 */
function uploadLogoAzienda(): void {
    if (!isset($_FILES['logo_file'])) {
        jsonResponse(false, null, 'Nessun file caricato');
        return;
    }
    
    $file = $_FILES['logo_file'];
    
    // Validazione
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
    if (!in_array($file['type'], $allowedTypes)) {
        jsonResponse(false, null, 'Formato non valido. Usa JPG, PNG, GIF, WEBP o SVG');
        return;
    }
    
    if ($file['size'] > 5 * 1024 * 1024) {
        jsonResponse(false, null, 'File troppo grande (max 5MB)');
        return;
    }
    
    // Crea directory
    $uploadDir = __DIR__ . '/../assets/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Nome file
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'logo_azienda_' . time() . '.' . $ext;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        global $pdo;
        $logoUrl = 'assets/uploads/' . $filename;
        
        $stmt = $pdo->prepare("
            INSERT INTO impostazioni (chiave, valore) 
            VALUES ('logo_azienda', ?)
            ON DUPLICATE KEY UPDATE valore = ?
        ");
        $stmt->execute([$logoUrl, $logoUrl]);
        
        jsonResponse(true, ['logo_url' => $logoUrl], 'Logo caricato con successo');
    } else {
        jsonResponse(false, null, 'Errore durante il caricamento');
    }
}

/**
 * Ottiene i codici ATECO
 */
function getCodiciAteco(): void {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT * FROM codici_ateco ORDER BY codice ASC");
        $codici = $stmt->fetchAll();
        jsonResponse(true, $codici);
    } catch (PDOException $e) {
        error_log("Errore get codici ateco: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento codici');
    }
}

/**
 * Ottiene le impostazioni tasse
 */
function getImpostazioniTasse(): void {
    global $pdo;
    
    try {
        $impostazioni = [];
        $chiavi = ['tassa_inps_percentuale', 'tassa_acconto_percentuale'];
        
        foreach ($chiavi as $chiave) {
            $stmt = $pdo->prepare("SELECT valore FROM impostazioni WHERE chiave = ?");
            $stmt->execute([$chiave]);
            $impostazioni[str_replace('tassa_', '', $chiave)] = floatval($stmt->fetchColumn() ?: 0);
        }
        
        jsonResponse(true, $impostazioni);
    } catch (PDOException $e) {
        error_log("Errore get impostazioni tasse: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento impostazioni');
    }
}

/**
 * Ottiene le impostazioni contabilita (periodo: mensile/settimanale/giornaliero)
 */
function getImpostazioniContabilita(): void {
    global $pdo;
    
    try {
        $impostazioni = [];
        $chiavi = [
            'contabilita_periodo' => 'mensile',
            'contabilita_giorno_inizio' => '1',
            'contabilita_mese_fiscale' => '1'
        ];
        
        foreach ($chiavi as $chiave => $default) {
            $stmt = $pdo->prepare("SELECT valore FROM impostazioni WHERE chiave = ?");
            $stmt->execute([$chiave]);
            $valore = $stmt->fetchColumn();
            $impostazioni[str_replace('contabilita_', '', $chiave)] = $valore ?: $default;
        }
        
        jsonResponse(true, $impostazioni);
    } catch (PDOException $e) {
        error_log("Errore get impostazioni contabilita: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento impostazioni');
    }
}

/**
 * Salva un codice ATECO (crea o aggiorna)
 */
function saveCodiceAteco(): void {
    global $pdo;
    
    // Verifica password
    $password = $_POST['password'] ?? '';
    if ($password !== 'Tomato2399!?') {
        jsonResponse(false, null, 'Password errata');
        return;
    }
    
    $id = $_POST['id'] ?? null;
    $codice = trim($_POST['codice'] ?? '');
    $descrizione = trim($_POST['descrizione'] ?? '');
    $coefficiente = floatval($_POST['coefficiente_redditivita'] ?? 0);
    $tassazione = floatval($_POST['tassazione'] ?? 0);
    
    if (empty($codice)) {
        jsonResponse(false, null, 'Il codice ATECO è obbligatorio');
        return;
    }
    
    try {
        if ($id) {
            // Update
            $stmt = $pdo->prepare("
                UPDATE codici_ateco 
                SET codice = ?, descrizione = ?, coefficiente_redditivita = ?, tassazione = ?
                WHERE id = ?
            ");
            $stmt->execute([$codice, $descrizione, $coefficiente, $tassazione, $id]);
        } else {
            // Insert
            $stmt = $pdo->prepare("
                INSERT INTO codici_ateco (codice, descrizione, coefficiente_redditivita, tassazione)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$codice, $descrizione, $coefficiente, $tassazione]);
            $id = $pdo->lastInsertId();
        }
        
        jsonResponse(true, ['id' => $id], 'Codice ATECO salvato con successo');
    } catch (PDOException $e) {
        error_log("Errore save codice ateco: " . $e->getMessage());
        jsonResponse(false, null, 'Errore durante il salvataggio');
    }
}

/**
 * Elimina un codice ATECO
 */
function deleteCodiceAteco(): void {
    global $pdo;
    
    // Verifica password
    $password = $_POST['password'] ?? '';
    if ($password !== 'Tomato2399!?') {
        jsonResponse(false, null, 'Password errata');
        return;
    }
    
    $id = $_POST['id'] ?? null;
    if (!$id) {
        jsonResponse(false, null, 'ID codice richiesto');
        return;
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM codici_ateco WHERE id = ?");
        $stmt->execute([$id]);
        jsonResponse(true, null, 'Codice ATECO eliminato');
    } catch (PDOException $e) {
        error_log("Errore delete codice ateco: " . $e->getMessage());
        jsonResponse(false, null, 'Errore durante eliminazione');
    }
}

/**
 * Salva le impostazioni tasse generali
 */
function saveImpostazioniTasse(): void {
    global $pdo;
    
    // Verifica password
    $password = $_POST['password'] ?? '';
    if ($password !== 'Tomato2399!?') {
        jsonResponse(false, null, 'Password errata');
        return;
    }
    
    $inps = floatval($_POST['inps_percentuale'] ?? 0);
    $acconto = floatval($_POST['acconto_percentuale'] ?? 0);
    
    try {
        // Salva INPS
        $stmt = $pdo->prepare("
            INSERT INTO impostazioni (chiave, valore, tipo, descrizione) 
            VALUES ('tassa_inps_percentuale', ?, 'number', 'Percentuale INPS')
            ON DUPLICATE KEY UPDATE valore = ?
        ");
        $stmt->execute([$inps, $inps]);
        
        // Salva acconto
        $stmt = $pdo->prepare("
            INSERT INTO impostazioni (chiave, valore, tipo, descrizione) 
            VALUES ('tassa_acconto_percentuale', ?, 'number', 'Percentuale acconto tasse')
            ON DUPLICATE KEY UPDATE valore = ?
        ");
        $stmt->execute([$acconto, $acconto]);
        
        jsonResponse(true, null, 'Impostazioni tasse salvate');
    } catch (PDOException $e) {
        error_log("Errore save impostazioni tasse: " . $e->getMessage());
        jsonResponse(false, null, 'Errore durante il salvataggio');
    }
}

/**
 * Salva le impostazioni contabilita (periodo, giorno inizio, etc.)
 */
function saveImpostazioniContabilita(): void {
    global $pdo;
    
    $periodo = $_POST['periodo'] ?? 'mensile';
    $giornoInizio = intval($_POST['giorno_inizio'] ?? 1);
    $meseFiscale = intval($_POST['mese_fiscale'] ?? 1);
    
    // Validazione
    $periodiValidi = ['giornaliero', 'settimanale', 'mensile'];
    if (!in_array($periodo, $periodiValidi)) {
        jsonResponse(false, null, 'Periodo non valido');
        return;
    }
    
    if ($giornoInizio < 1 || $giornoInizio > 31) {
        $giornoInizio = 1;
    }
    
    if ($meseFiscale < 1 || $meseFiscale > 12) {
        $meseFiscale = 1;
    }
    
    try {
        // Salva periodo
        $stmt = $pdo->prepare("
            INSERT INTO impostazioni (chiave, valore, tipo, descrizione) 
            VALUES ('contabilita_periodo', ?, 'text', 'Periodo contabilita')
            ON DUPLICATE KEY UPDATE valore = ?
        ");
        $stmt->execute([$periodo, $periodo]);
        
        // Salva giorno inizio
        $stmt = $pdo->prepare("
            INSERT INTO impostazioni (chiave, valore, tipo, descrizione) 
            VALUES ('contabilita_giorno_inizio', ?, 'number', 'Giorno inizio periodo')
            ON DUPLICATE KEY UPDATE valore = ?
        ");
        $stmt->execute([$giornoInizio, $giornoInizio]);
        
        // Salva mese fiscale
        $stmt = $pdo->prepare("
            INSERT INTO impostazioni (chiave, valore, tipo, descrizione) 
            VALUES ('contabilita_mese_fiscale', ?, 'number', 'Mese inizio anno fiscale')
            ON DUPLICATE KEY UPDATE valore = ?
        ");
        $stmt->execute([$meseFiscale, $meseFiscale]);
        
        jsonResponse(true, null, 'Impostazioni contabilita salvate');
    } catch (PDOException $e) {
        error_log("Errore save impostazioni contabilita: " . $e->getMessage());
        jsonResponse(false, null, 'Errore durante il salvataggio');
    }
}


/**
 * Cambia la password dell'utente corrente
 */
function changePassword(): void {
    global $pdo;
    
    $userId = $_SESSION['user_id'] ?? '';
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    
    if (empty($userId)) {
        jsonResponse(false, null, 'Utente non autenticato');
        return;
    }
    
    if (empty($currentPassword) || empty($newPassword)) {
        jsonResponse(false, null, 'Compila tutti i campi');
        return;
    }
    
    if (strlen($newPassword) < 6) {
        jsonResponse(false, null, 'La nuova password deve essere di almeno 6 caratteri');
        return;
    }
    
    try {
        // Recupera l'utente
        $stmt = $pdo->prepare("SELECT id, password FROM utenti WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            jsonResponse(false, null, 'Utente non trovato');
            return;
        }
        
        // Verifica password attuale
        if (!password_verify($currentPassword, $user['password'])) {
            jsonResponse(false, null, 'Password attuale errata');
            return;
        }
        
        // Hash nuova password
        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        
        // Aggiorna password
        $stmt = $pdo->prepare("UPDATE utenti SET password = ? WHERE id = ?");
        $stmt->execute([$newHash, $userId]);
        
        // Log
        logTimeline($userId, 'changed_password', 'utente', $userId, 'Password modificata');
        
        jsonResponse(true, null, 'Password aggiornata con successo');
        
    } catch (PDOException $e) {
        error_log("Errore cambio password: " . $e->getMessage());
        jsonResponse(false, null, 'Errore durante il cambio password');
    }
}
