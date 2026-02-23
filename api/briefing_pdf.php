<?php
/**
 * API per generazione PDF vettoriale da HTML
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAuth();

$input = json_decode(file_get_contents('php://input'), true);
$html = $input['html'] ?? '';
$filename = sanitizeInput($input['filename'] ?? 'briefing.pdf');

if (empty($html)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'HTML mancante']);
    exit;
}

// Carica autoload con tutte le dipendenze
$autoloadPath = $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    header('Content-Type: application/json');
    http_response_code(501);
    echo json_encode(['error' => 'PDF_NON_CONFIGURATO', 'message' => 'Autoload non trovato']);
    exit;
}

require_once $autoloadPath;

// Verifica che Dompdf esista
if (!class_exists('Dompdf\Dompdf')) {
    header('Content-Type: application/json');
    http_response_code(501);
    echo json_encode(['error' => 'PDF_NON_CONFIGURATO', 'message' => 'Dompdf non caricato']);
    exit;
}

try {
    $dompdf = new \Dompdf\Dompdf([
        'isRemoteEnabled' => false,
        'isHtml5ParserEnabled' => true,
        'defaultFont' => 'helvetica'
    ]);
    
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    $pdfOutput = $dompdf->output();
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdfOutput));
    echo $pdfOutput;
    exit;
    
} catch (Throwable $e) {
    error_log("[Briefing PDF] Errore: " . $e->getMessage());
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'ERRORE_PDF', 'message' => $e->getMessage()]);
}
