<?php
/**
 * API per la gestione delle checklist di controllo progetti
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Verifica autenticazione
requireAuth();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    global $pdo;
    
    if (!isset($pdo)) {
        throw new Exception('Database non inizializzato');
    }
    
    switch ($method) {
        case 'GET':
            // Carica checklist per un progetto
            $progetto_id = $_GET['progetto_id'] ?? '';
            
            if (empty($progetto_id)) {
                http_response_code(400);
                echo json_encode(['error' => 'ID progetto mancante']);
                exit;
            }
            
            // Verifica tabella esiste
            try {
                $pdo->query("SELECT 1 FROM progetti_checklist LIMIT 1");
            } catch (PDOException $e) {
                // Tabella non esiste, creala
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS progetti_checklist (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        progetto_id VARCHAR(20) NOT NULL,
                        tipologia VARCHAR(50) NOT NULL,
                        checklist_data TEXT NOT NULL,
                        linguaggio_sito VARCHAR(100),
                        ultimo_salvataggio DATETIME DEFAULT CURRENT_TIMESTAMP,
                        UNIQUE KEY unique_checklist (progetto_id, tipologia),
                        FOREIGN KEY (progetto_id) REFERENCES progetti(id) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                ");
            }
            
            $stmt = $pdo->prepare("SELECT * FROM progetti_checklist WHERE progetto_id = ?");
            $stmt->execute([$progetto_id]);
            $checklists = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Formatta i dati
            $result = [];
            foreach ($checklists as $c) {
                $result[$c['tipologia']] = [
                    'checklist' => json_decode($c['checklist_data'], true),
                    'linguaggio_sito' => $c['linguaggio_sito'],
                    'ultimo_salvataggio' => $c['ultimo_salvataggio']
                ];
            }
            
            echo json_encode(['success' => true, 'data' => $result]);
            break;
            
        case 'POST':
            // Salva/aggiorna checklist
            $progetto_id = $input['progetto_id'] ?? '';
            $tipologia = sanitizeInput($input['tipologia'] ?? '');
            $checklist = $input['checklist'] ?? [];
            $linguaggio_sito = sanitizeInput($input['linguaggio_sito'] ?? '');
            
            if (empty($progetto_id) || empty($tipologia)) {
                http_response_code(400);
                echo json_encode(['error' => 'Dati mancanti']);
                exit;
            }
            
            // Insert o update
            $stmt = $pdo->prepare("
                INSERT INTO progetti_checklist (progetto_id, tipologia, checklist_data, linguaggio_sito, ultimo_salvataggio)
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    checklist_data = VALUES(checklist_data),
                    linguaggio_sito = VALUES(linguaggio_sito),
                    ultimo_salvataggio = NOW()
            ");
            
            $stmt->execute([
                $progetto_id,
                $tipologia,
                json_encode($checklist),
                $linguaggio_sito ?: null
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Checklist salvata',
                'ultimo_salvataggio' => date('Y-m-d H:i:s')
            ]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Metodo non consentito']);
    }
    
} catch (Throwable $e) {
    error_log("Errore checklist: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Errore: ' . $e->getMessage()]);
}
