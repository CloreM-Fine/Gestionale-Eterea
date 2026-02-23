<?php
/**
 * Eterea Gestionale
 * API Impostazioni di sistema
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Solo admin può accedere alle impostazioni avanzate
// (opzionale - togliere il commento se vuoi limitare l'accesso)
// if ($_SESSION['user_id'] !== 'ugv7adudxudhx') {
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
        jsonResponse(false, null, 'Errore durante l\'eliminazione');
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
        $utentiConSaldo = $stmt->fetchAll();
        
        // 2. Elimina prima tutte le transazioni economiche (per evitare FK constraint)
        $stmt = $pdo->prepare("DELETE FROM transazioni_economiche");
        $stmt->execute();
        
        // 3. Azzera tutti i saldi utenti
        $stmt = $pdo->prepare("UPDATE utenti SET wallet_saldo = 0");
        $stmt->execute();
        
        // 4. Log dell'operazione (dopo il commit per sicurezza)
        $pdo->commit();
        
        // Log fuori dalla transazione
        foreach ($utentiConSaldo as $u) {
            logTimeline($_SESSION['user_id'], 'reset_saldo', 'utente', $u['id'], 
                "Azzerato saldo di {$u['nome']} (era: €{$u['wallet_saldo']})");
        }
        
        logTimeline($_SESSION['user_id'], 'reset_cassa', 'sistema', '', 
            "Azzerata cassa aziendale da impostazioni");
        
        jsonResponse(true, null, 'Saldi e cassa aziendale azzerati con successo');
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Errore reset saldi: " . $e->getMessage());
        jsonResponse(false, null, 'Errore durante l\'azzeramento: ' . $e->getMessage());
    }
}

/**
 * Elimina TUTTI i dati (operazione distruttiva)
 */
function deleteAll(string $keyword): void {
    global $pdo;
    
    // Verifica parola chiave
    $keywordCorretta = 'Tomato2399Andromeda2399!?';
    if ($keyword !== $keywordCorretta) {
        jsonResponse(false, null, 'Parola chiave errata');
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Disabilita foreign key checks temporaneamente
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        // Elimina tutti i dati (ordine non importante con FK disabilitati)
        $pdo->exec("DELETE FROM timeline");
        $pdo->exec("DELETE FROM transazioni_economiche");
        $pdo->exec("DELETE FROM task_allegati");
        $pdo->exec("DELETE FROM task");
        $pdo->exec("DELETE FROM appuntamenti");
        $pdo->exec("DELETE FROM progetti");
        $pdo->exec("DELETE FROM clienti");
        
        // Azzera saldi utenti (ma lascia gli utenti)
        $pdo->exec("UPDATE utenti SET wallet_saldo = 0");
        
        // Riabilita foreign key checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        $pdo->commit();
        
        // Log fuori dalla transazione
        logTimeline($_SESSION['user_id'], 'delete_all', 'sistema', '', 
            "ELIMINAZIONE TOTALE DATI eseguita da impostazioni");
        
        jsonResponse(true, null, 'Tutti i dati sono stati eliminati');
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        // Assicurati di riabilitare FK anche in caso di errore
        try {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        } catch (PDOException $e2) {
            // Ignora
        }
        error_log("Errore eliminazione totale: " . $e->getMessage());
        jsonResponse(false, null, 'Errore durante l\'eliminazione: ' . $e->getMessage());
    }
}

/**
 * Esporta backup in CSV
 */
function exportBackup(string $tipo): void {
    global $pdo;
    
    $filename = '';
    $headers = [];
    $data = [];
    
    try {
        switch ($tipo) {
            case 'clienti':
                $filename = 'backup_clienti_' . date('Y-m-d') . '.csv';
                $stmt = $pdo->query("SELECT * FROM clienti ORDER BY ragione_sociale ASC");
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
                
            case 'progetti':
                $filename = 'backup_progetti_' . date('Y-m-d') . '.csv';
                $stmt = $pdo->query("
                    SELECT p.*, c.ragione_sociale as cliente_nome 
                    FROM progetti p 
                    LEFT JOIN clienti c ON p.cliente_id = c.id 
                    ORDER BY p.created_at DESC
                ");
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
                
            case 'finanze':
                $filename = 'backup_finanze_' . date('Y-m-d') . '.csv';
                $stmt = $pdo->query("
                    SELECT t.*, u.nome as utente_nome 
                    FROM transazioni_economiche t 
                    LEFT JOIN utenti u ON t.utente_id = u.id 
                    ORDER BY t.created_at DESC
                ");
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
                
            case 'appuntamenti':
                $filename = 'backup_appuntamenti_' . date('Y-m-d') . '.csv';
                $stmt = $pdo->query("
                    SELECT a.*, p.titolo as progetto_titolo, u.nome as utente_nome 
                    FROM appuntamenti a 
                    LEFT JOIN progetti p ON a.progetto_id = p.id 
                    LEFT JOIN utenti u ON a.utente_id = u.id 
                    ORDER BY a.data_inizio DESC
                ");
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
                
            default:
                jsonResponse(false, null, 'Tipo di backup non valido');
                return;
        }
        
        if (empty($data)) {
            jsonResponse(false, null, 'Nessun dato da esportare');
            return;
        }
        
        // Headers
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // BOM per Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Header
        fputcsv($output, array_keys($data[0]), ';');
        
        // Dati
        foreach ($data as $row) {
            fputcsv($output, $row, ';');
        }
        
        fclose($output);
        exit;
        
    } catch (PDOException $e) {
        error_log("Errore backup: " . $e->getMessage());
        jsonResponse(false, null, 'Errore durante l\'esportazione');
    }
}

/**
 * Upload avatar utente
 */
function uploadAvatar(): void {
    global $pdo;
    
    // Verifica file
    if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(false, null, 'Nessun file caricato');
        return;
    }
    
    $file = $_FILES['avatar'];
    
    // Validazione tipo
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        jsonResponse(false, null, 'Formato non valido. Usa JPG, PNG, GIF o WEBP');
        return;
    }
    
    // Validazione dimensione (max 2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        jsonResponse(false, null, 'File troppo grande. Max 2MB');
        return;
    }
    
    // Crea directory se non esiste
    $uploadDir = __DIR__ . '/../assets/uploads/avatars/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Genera nome file univoco
    $extension = match($mimeType) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        default => 'jpg'
    };
    
    $userId = $_SESSION['user_id'];
    $filename = $userId . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    try {
        // Elimina avatar precedente se esiste
        $stmt = $pdo->prepare("SELECT avatar FROM utenti WHERE id = ?");
        $stmt->execute([$userId]);
        $oldAvatar = $stmt->fetchColumn();
        
        if ($oldAvatar && file_exists($uploadDir . $oldAvatar)) {
            unlink($uploadDir . $oldAvatar);
        }
        
        // Ridimensiona e comprimi l'immagine
        $image = null;
        switch ($mimeType) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($file['tmp_name']);
                break;
            case 'image/png':
                $image = imagecreatefrompng($file['tmp_name']);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($file['tmp_name']);
                break;
            case 'image/webp':
                $image = imagecreatefromwebp($file['tmp_name']);
                break;
        }
        
        if (!$image) {
            jsonResponse(false, null, 'Errore elaborazione immagine');
            return;
        }
        
        // Ridimensiona a 400x400 mantenendo proporzioni
        $width = imagesx($image);
        $height = imagesy($image);
        $size = min($width, $height);
        $newSize = 400;
        
        $newImage = imagecreatetruecolor($newSize, $newSize);
        
        // Preserva trasparenza per PNG
        if ($mimeType === 'image/png') {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
        }
        
        // Ritaglia quadrato centrato
        $srcX = ($width - $size) / 2;
        $srcY = ($height - $size) / 2;
        
        imagecopyresampled($newImage, $image, 0, 0, $srcX, $srcY, $newSize, $newSize, $size, $size);
        
        // Salva l'immagine
        switch ($extension) {
            case 'jpg':
                imagejpeg($newImage, $filepath, 85);
                break;
            case 'png':
                imagepng($newImage, $filepath, 8);
                break;
            case 'gif':
                imagegif($newImage, $filepath);
                break;
            case 'webp':
                imagewebp($newImage, $filepath, 85);
                break;
        }
        
        imagedestroy($image);
        imagedestroy($newImage);
        
        // Aggiorna database
        $stmt = $pdo->prepare("UPDATE utenti SET avatar = ? WHERE id = ?");
        $result = $stmt->execute([$filename, $userId]);
        error_log("Avatar update result: " . ($result ? 'success' : 'failed') . " - rows affected: " . $stmt->rowCount());
        
        // Aggiorna sessione
        $_SESSION['user_avatar'] = $filename;
        
        jsonResponse(true, ['avatar' => $filename], 'Avatar aggiornato con successo');
        
    } catch (Exception $e) {
        error_log("Errore upload avatar: " . $e->getMessage());
        jsonResponse(false, null, 'Errore durante l\'upload');
    }
}


/**
 * Ottieni il logo del gestionale
 */
function getLogo(): void {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT valore FROM impostazioni WHERE chiave = 'logo_gestionale'");
        $stmt->execute();
        $logo = $stmt->fetchColumn() ?: '';
        
        // Verifica se è un SVG
        $isSvg = false;
        if ($logo && str_ends_with(strtolower($logo), '.svg')) {
            $isSvg = true;
        }
        
        jsonResponse(true, [
            'logo' => $logo,
            'is_svg' => $isSvg,
            'full_path' => $logo ? 'assets/uploads/logo/' . $logo : ''
        ]);
    } catch (PDOException $e) {
        error_log("Errore get logo: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento logo');
    }
}

/**
 * Salva il logo del gestionale
 */
function saveLogo(): void {
    global $pdo;
    
    // Se è richiesta la rimozione
    if (!empty($_POST['remove']) && $_POST['remove'] === 'true') {
        try {
            $stmt = $pdo->prepare("SELECT valore FROM impostazioni WHERE chiave = 'logo_gestionale'");
            $stmt->execute();
            $oldLogo = $stmt->fetchColumn();
            
            if ($oldLogo) {
                $uploadDir = __DIR__ . '/../assets/uploads/logo/';
                if (file_exists($uploadDir . $oldLogo)) {
                    unlink($uploadDir . $oldLogo);
                }
                
                $stmt = $pdo->prepare("UPDATE impostazioni SET valore = '' WHERE chiave = 'logo_gestionale'");
                $stmt->execute();
            }
            
            jsonResponse(true, ['logo' => ''], 'Logo rimosso con successo');
            return;
        } catch (Exception $e) {
            error_log("Errore rimozione logo: " . $e->getMessage());
            jsonResponse(false, null, 'Errore durante la rimozione');
            return;
        }
    }
    
    // Verifica file
    if (empty($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(false, null, 'Nessun file caricato');
        return;
    }
    
    $file = $_FILES['logo'];
    
    // Validazione tipo (immagini + SVG)
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    // Per SVG, finfo potrebbe non riconoscerlo correttamente, quindi controlliamo anche l'estensione
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $isSvg = ($extension === 'svg') || ($mimeType === 'image/svg+xml');
    
    if (!in_array($mimeType, $allowedTypes) && !$isSvg) {
        jsonResponse(false, null, 'Formato non valido. Usa JPG, PNG, GIF, WEBP o SVG');
        return;
    }
    
    // Validazione dimensione (max 5MB per SVG, 2MB per immagini)
    $maxSize = $isSvg ? 5 * 1024 * 1024 : 2 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        jsonResponse(false, null, 'File troppo grande. Max ' . ($isSvg ? '5MB' : '2MB'));
        return;
    }
    
    // Crea directory se non esiste
    $uploadDir = __DIR__ . '/../assets/uploads/logo/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Genera nome file univoco
    $extension = $isSvg ? 'svg' : match($mimeType) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        default => 'png'
    };
    
    $filename = 'logo_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    try {
        // Elimina logo precedente se esiste
        $stmt = $pdo->prepare("SELECT valore FROM impostazioni WHERE chiave = 'logo_gestionale'");
        $stmt->execute();
        $oldLogo = $stmt->fetchColumn();
        
        if ($oldLogo && file_exists($uploadDir . $oldLogo)) {
            unlink($uploadDir . $oldLogo);
        }
        
        // Se è un'immagine (non SVG), ridimensionala
        if (!$isSvg) {
            $image = null;
            switch ($mimeType) {
                case 'image/jpeg':
                    $image = imagecreatefromjpeg($file['tmp_name']);
                    break;
                case 'image/png':
                    $image = imagecreatefrompng($file['tmp_name']);
                    break;
                case 'image/gif':
                    $image = imagecreatefromgif($file['tmp_name']);
                    break;
                case 'image/webp':
                    $image = imagecreatefromwebp($file['tmp_name']);
                    break;
            }
            
            if ($image) {
                // Ridimensiona mantenendo proporzioni (max 400px altezza)
                $width = imagesx($image);
                $height = imagesy($image);
                $maxHeight = 400;
                
                if ($height > $maxHeight) {
                    $newHeight = $maxHeight;
                    $newWidth = intval($width * ($maxHeight / $height));
                    
                    $newImage = imagecreatetruecolor($newWidth, $newHeight);
                    
                    // Preserva trasparenza per PNG
                    if ($mimeType === 'image/png') {
                        imagealphablending($newImage, false);
                        imagesavealpha($newImage, true);
                    }
                    
                    imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    
                    // Salva l'immagine
                    switch ($extension) {
                        case 'jpg':
                            imagejpeg($newImage, $filepath, 90);
                            break;
                        case 'png':
                            imagepng($newImage, $filepath, 8);
                            break;
                        case 'gif':
                            imagegif($newImage, $filepath);
                            break;
                        case 'webp':
                            imagewebp($newImage, $filepath, 90);
                            break;
                    }
                    
                    imagedestroy($newImage);
                } else {
                    // Nessun ridimensionamento necessario
                    move_uploaded_file($file['tmp_name'], $filepath);
                }
                
                imagedestroy($image);
            } else {
                move_uploaded_file($file['tmp_name'], $filepath);
            }
        } else {
            // Per SVG, salva direttamente
            move_uploaded_file($file['tmp_name'], $filepath);
        }
        
        // Salva nel database
        $stmt = $pdo->prepare("INSERT INTO impostazioni (chiave, valore, tipo, descrizione) VALUES ('logo_gestionale', ?, 'image', 'Logo del gestionale') ON DUPLICATE KEY UPDATE valore = ?");
        $stmt->execute([$filename, $filename]);
        
        logTimeline($_SESSION['user_id'], 'aggiornato_logo', 'sistema', '', 'Logo gestionale aggiornato');
        
        jsonResponse(true, ['logo' => $filename, 'is_svg' => $isSvg], 'Logo aggiornato con successo');
        
    } catch (Exception $e) {
        error_log("Errore save logo: " . $e->getMessage());
        jsonResponse(false, null, 'Errore durante il salvataggio del logo');
    }
}
