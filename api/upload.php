<?php
/**
 * Eterea Gestionale
 * API Upload File
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'task':
        uploadTaskFile();
        break;
        
    case 'delete':
        deleteFile();
        break;
        
    case 'download':
        downloadFile();
        break;
        
    default:
        jsonResponse(false, null, 'Azione non valida');
}

/**
 * Upload file per task
 */
function uploadTaskFile(): void {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(false, null, 'Metodo non consentito');
    }
    
    // Rate limiting upload
    if (!checkRateLimit('upload_' . ($_SESSION['user_id'] ?? 'guest'), 10, 5)) {
        securityLog('Upload rate limit exceeded');
        jsonResponse(false, null, 'Troppi upload in poco tempo. Riprova più tardi.');
    }
    
    $taskId = $_POST['task_id'] ?? '';
    if (empty($taskId)) {
        jsonResponse(false, null, 'ID task mancante');
    }
    
    // Verifica task esista
    $stmt = $pdo->prepare("SELECT progetto_id FROM task WHERE id = ?");
    $stmt->execute([$taskId]);
    $task = $stmt->fetch();
    if (!$task) {
        jsonResponse(false, null, 'Task non trovata');
    }
    
    // Verifica numero file esistenti (max 5)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM task_allegati WHERE task_id = ?");
    $stmt->execute([$taskId]);
    if ($stmt->fetchColumn() >= 5) {
        jsonResponse(false, null, 'Limite massimo di 5 file per task raggiunto');
    }
    
    // Verifica file
    if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(false, null, 'Nessun file caricato');
    }
    
    // Upload con security hardening
    $upload = uploadFileSecure(
        $_FILES['file'], 
        'task_files', 
        ['application/pdf'], 
        MAX_UPLOAD_SIZE_MB * 1024 * 1024,
        true // randomizza nome
    );
    
    if (!$upload) {
        jsonResponse(false, null, 'Errore durante il caricamento. Verifica che il file sia PDF e non superi 10MB');
    }
    
    try {
        // Salva nel database
        $stmt = $pdo->prepare("
            INSERT INTO task_allegati (task_id, filename, file_path, file_size, uploaded_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $taskId,
            $_FILES['file']['name'],
            $upload['path'],
            $upload['size'],
            $_SESSION['user_id']
        ]);
        
        $allegatoId = $pdo->lastInsertId();
        
        logTimeline($_SESSION['user_id'], 'upload_file', 'file', $allegatoId, "Caricato: {$_FILES['file']['name']}");
        
        jsonResponse(true, [
            'id' => $allegatoId,
            'filename' => $_FILES['file']['name'],
            'size' => $upload['size']
        ], 'File caricato con successo');
        
    } catch (PDOException $e) {
        // Elimina file fisico se errore DB
        @unlink(UPLOAD_PATH . $upload['path']);
        error_log("Errore upload: " . $e->getMessage());
        jsonResponse(false, null, 'Errore salvataggio file');
    }
}

/**
 * Elimina file
 */
function deleteFile(): void {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(false, null, 'Metodo non consentito');
    }
    
    $fileId = $_POST['file_id'] ?? '';
    if (empty($fileId)) {
        jsonResponse(false, null, 'ID file mancante');
    }
    
    try {
        // Recupera info file
        $stmt = $pdo->prepare("
            SELECT ta.*, t.progetto_id 
            FROM task_allegati ta
            JOIN task t ON ta.task_id = t.id
            WHERE ta.id = ?
        ");
        $stmt->execute([$fileId]);
        $file = $stmt->fetch();
        
        if (!$file) {
            jsonResponse(false, null, 'File non trovato');
        }
        
        // Verifica permessi (solo chi ha caricato o admin)
        // Per ora permettiamo a tutti gli utenti autenticati
        
        // Elimina fisico
        $fullPath = UPLOAD_PATH . $file['file_path'];
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
        
        // Elimina da DB
        $stmt = $pdo->prepare("DELETE FROM task_allegati WHERE id = ?");
        $stmt->execute([$fileId]);
        
        logTimeline($_SESSION['user_id'], 'eliminato_file', 'file', $fileId, "Eliminato: {$file['filename']}");
        
        jsonResponse(true, null, 'File eliminato');
        
    } catch (PDOException $e) {
        error_log("Errore eliminazione file: " . $e->getMessage());
        jsonResponse(false, null, 'Errore eliminazione file');
    }
}

/**
 * Download file tramite proxy
 */
function downloadFile(): void {
    global $pdo;
    
    $fileId = $_GET['file_id'] ?? '';
    if (empty($fileId)) {
        http_response_code(400);
        die('ID file mancante');
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM task_allegati WHERE id = ?");
        $stmt->execute([$fileId]);
        $file = $stmt->fetch();
        
        if (!$file) {
            http_response_code(404);
            die('File non trovato');
        }
        
        $fullPath = UPLOAD_PATH . $file['file_path'];
        if (!file_exists($fullPath)) {
            http_response_code(404);
            die('File non trovato sul server');
        }
        
        // Header download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $file['filename'] . '"');
        header('Content-Length: ' . filesize($fullPath));
        header('Cache-Control: no-cache, must-revalidate');
        
        readfile($fullPath);
        exit;
        
    } catch (PDOException $e) {
        http_response_code(500);
        die('Errore server');
    }
}
