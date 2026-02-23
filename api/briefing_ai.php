<?php
/**
 * API per salvare il briefing nel progetto
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAuth();
header('Content-Type: application/json');

try {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_to_project') {
        $progettoId = $_POST['progetto_id'] ?? '';
        $filename = sanitizeInput($_POST['filename'] ?? 'briefing.pdf');
        
        if (empty($progettoId)) {
            echo json_encode(['success' => false, 'message' => 'Progetto non specificato']);
            exit;
        }
        
        if (!isset($_FILES['documento']) || $_FILES['documento']['error'] !== UPLOAD_ERR_OK) {
            $error_msg = 'File non ricevuto';
            if (isset($_FILES['documento'])) {
                $error_msg .= ' - Error code: ' . $_FILES['documento']['error'];
            }
            echo json_encode(['success' => false, 'message' => $error_msg]);
            exit;
        }
        
        // Verifica progetto esiste
        $stmt = $pdo->prepare("SELECT id FROM progetti WHERE id = ?");
        $stmt->execute([$progettoId]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Progetto non trovato']);
            exit;
        }
        
        // Crea cartella se non esiste
        $uploadDir = __DIR__ . '/../assets/uploads/progetti/' . $progettoId . '/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                echo json_encode(['success' => false, 'message' => 'Errore creazione cartella: ' . $uploadDir]);
                exit;
            }
        }
        
        // Nome file univoco
        $ext = pathinfo($filename, PATHINFO_EXTENSION) ?: 'pdf';
        $fileName = 'briefing_' . time() . '_' . uniqid() . '.' . $ext;
        $filePath = $uploadDir . $fileName;
        
        // Sposta file
        if (!move_uploaded_file($_FILES['documento']['tmp_name'], $filePath)) {
            echo json_encode(['success' => false, 'message' => 'Errore spostamento file upload']);
            exit;
        }
        
        // Verifica tabella esiste
        try {
            $pdo->query("SELECT 1 FROM progetto_documenti LIMIT 1");
        } catch (PDOException $e) {
            // Tabella non esiste, creala
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS progetto_documenti (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    progetto_id VARCHAR(20) NOT NULL,
                    nome_file VARCHAR(255) NOT NULL,
                    file_path VARCHAR(500) NOT NULL,
                    tipo VARCHAR(50) DEFAULT 'generico',
                    uploaded_by VARCHAR(20),
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE,
                    FOREIGN KEY (uploaded_by) REFERENCES utenti(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
        
        // Salva nel database
        $stmt = $pdo->prepare("
            INSERT INTO progetto_documenti (progetto_id, nome_file, file_path, tipo, uploaded_by, created_at)
            VALUES (?, ?, ?, 'briefing', ?, NOW())
        ");
        
        $webPath = 'assets/uploads/progetti/' . $progettoId . '/' . $fileName;
        
        if ($stmt->execute([$progettoId, $filename, $webPath, currentUserId()])) {
            echo json_encode([
                'success' => true,
                'message' => 'Briefing salvato correttamente',
                'file_path' => $webPath
            ]);
        } else {
            // Rimuovi file se fallito
            unlink($filePath);
            echo json_encode(['success' => false, 'message' => 'Errore database: ' . implode(', ', $stmt->errorInfo())]);
        }
        
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Azione non valida: ' . $action]);
    
} catch (Throwable $e) {
    error_log("[Briefing AI] Errore: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Errore: ' . $e->getMessage()]);
}
