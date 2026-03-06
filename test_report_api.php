<?php
/**
 * Eterea Gestionale
 * Script di test per l'API Report
 * 
 * Eseguibile via browser per verificare che tutti gli endpoint
 * dell'API report funzionino correttamente.
 */

// Abilita visualizzazione errori PHP
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Includi configurazione DB e funzioni (senza auth_check)
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Costanti per gli endpoint
$baseUrl = BASE_URL . '/api/report.php';
$endpoints = [
    'dashboard' => '?action=dashboard',
    'utenti' => '?action=utenti',
    'progetti' => '?action=progetti',
    'economico' => '?action=economico',
    'temporale' => '?action=temporale',
];

// Funzione per fare richieste HTTP GET
function makeRequest(string $url) {
    $result = [
        'url' => $url,
        'status_code' => 0,
        'response' => '',
        'is_json' => false,
        'decoded' => null,
        'success' => null,
        'error' => null
    ];
    
    // Usa cURL se disponibile
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
        
        $response = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        $result['status_code'] = $httpCode;
        
        if ($curlError) {
            $result['error'] = 'cURL Error: ' . $curlError;
            return $result;
        }
        
        // Estrai body dalla risposta
        $body = substr($response, $headerSize);
        $result['response'] = $body;
    } else {
        // Fallback a file_get_contents
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'header' => 'Cookie: ' . session_name() . '=' . session_id() . "\r\n"
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        
        $headers = get_headers($url, 1);
        if ($headers !== false && isset($headers[0])) {
            preg_match('/HTTP\/\d\.\d\s+(\d+)/', $headers[0], $matches);
            $result['status_code'] = intval($matches[1] ?? 0);
        }
        
        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            $result['error'] = 'file_get_contents failed';
            return $result;
        }
        $result['response'] = $body;
    }
    
    // Verifica se è JSON valido
    $decoded = json_decode($body, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $result['is_json'] = true;
        $result['decoded'] = $decoded;
        $result['success'] = $decoded['success'] ?? null;
    } else {
        $result['is_json'] = false;
        $result['error'] = 'JSON Error: ' . json_last_error_msg();
    }
    
    return $result;
}

// Funzione per mostrare lo status code con colore
function formatStatusCode(int $code) {
    if ($code >= 200 && $code < 300) {
        return '<span class="status-success">' . $code . ' OK</span>';
    } elseif ($code >= 400 && $code < 500) {
        return '<span class="status-warning">' . $code . ' Client Error</span>';
    } elseif ($code >= 500) {
        return '<span class="status-error">' . $code . ' Server Error</span>';
    }
    return '<span class="status-info">' . $code . '</span>';
}

// Funzione per mostrare il successo
function formatSuccess(?bool $success) {
    if ($success === true) {
        return '<span class="badge-success">TRUE ✓</span>';
    } elseif ($success === false) {
        return '<span class="badge-error">FALSE ✗</span>';
    }
    return '<span class="badge-warning">N/A</span>';
}

// Avvia sessione per autenticazione
session_start();

// Se l'utente non è loggato, prova a fare login automatico con un utente esistente
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // Usa il primo utente dalla lista USERS
    $userIds = array_keys(USERS);
    if (!empty($userIds)) {
        $_SESSION['user_id'] = $userIds[0];
        $_SESSION['user_name'] = USERS[$userIds[0]]['nome'];
    }
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test API Report - Eterea Gestionale</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .status-success { color: #10B981; font-weight: 600; }
        .status-warning { color: #F59E0B; font-weight: 600; }
        .status-error { color: #EF4444; font-weight: 600; }
        .status-info { color: #6B7280; }
        .badge-success { background: #D1FAE5; color: #065F46; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .badge-error { background: #FEE2E2; color: #991B1B; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .badge-warning { background: #FEF3C7; color: #92400E; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .json-preview { background: #1F2937; color: #E5E7EB; padding: 12px; border-radius: 6px; font-family: monospace; font-size: 12px; max-height: 200px; overflow-y: auto; white-space: pre-wrap; word-break: break-word; }
        .endpoint-card { border-left: 4px solid #E5E7EB; }
        .endpoint-card.success { border-left-color: #10B981; }
        .endpoint-card.warning { border-left-color: #F59E0B; }
        .endpoint-card.error { border-left-color: #EF4444; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-5xl mx-auto p-6">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">🧪 Test API Report</h1>
                    <p class="text-gray-600 mt-1">Verifica funzionamento endpoint API report</p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-500">Sessione utente</div>
                    <div class="font-medium text-gray-900">
                        <?php echo isset($_SESSION['user_name']) ? e($_SESSION['user_name']) : 'Non autenticato'; ?>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">
                        ID: <?php echo isset($_SESSION['user_id']) ? e($_SESSION['user_id']) : 'N/A'; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- PHP Errors Section -->
        <?php if (!empty($phpErrors)): ?>
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
            <h3 class="text-red-800 font-semibold mb-2">⚠️ Errori PHP rilevati</h3>
            <ul class="text-red-700 text-sm space-y-1">
                <?php foreach ($phpErrors as $error): ?>
                <li><?php echo e($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Config Info -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
            <h3 class="text-blue-800 font-semibold mb-2">ℹ️ Configurazione</h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-blue-600">Base URL:</span>
                    <code class="bg-blue-100 px-2 py-1 rounded ml-2"><?php echo e(BASE_URL); ?></code>
                </div>
                <div>
                    <span class="text-blue-600">API Endpoint:</span>
                    <code class="bg-blue-100 px-2 py-1 rounded ml-2">/api/report.php</code>
                </div>
            </div>
        </div>

        <!-- Test Results -->
        <div class="space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">Risultati Test</h2>
            
            <?php foreach ($endpoints as $name => $queryString): 
                $url = $baseUrl . $queryString;
                $result = makeRequest($url);
                $cardClass = 'endpoint-card';
                if ($result['status_code'] >= 200 && $result['status_code'] < 300 && $result['success'] === true) {
                    $cardClass .= ' success';
                } elseif ($result['status_code'] >= 400 && $result['status_code'] < 500) {
                    $cardClass .= ' warning';
                } else {
                    $cardClass .= ' error';
                }
            ?>
            <div class="bg-white rounded-xl shadow-sm p-6 <?php echo $cardClass; ?>">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900"><?php echo e(ucfirst($name)); ?></h3>
                        <code class="text-sm text-gray-500 mt-1 block"><?php echo e($result['url']); ?></code>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-medium">HTTP:</span>
                        <?php echo formatStatusCode($result['status_code']); ?>
                    </div>
                </div>
                
                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div class="bg-gray-50 rounded-lg p-3">
                        <div class="text-xs text-gray-500 uppercase tracking-wide">JSON Valido</div>
                        <div class="mt-1">
                            <?php if ($result['is_json']): ?>
                                <span class="badge-success">SÌ ✓</span>
                            <?php else: ?>
                                <span class="badge-error">NO ✗</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <div class="text-xs text-gray-500 uppercase tracking-wide">Success</div>
                        <div class="mt-1">
                            <?php echo formatSuccess($result['success']); ?>
                        </div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <div class="text-xs text-gray-500 uppercase tracking-wide">Message</div>
                        <div class="mt-1 text-sm text-gray-700 truncate">
                            <?php echo e($result['decoded']['message'] ?? '-'); ?>
                        </div>
                    </div>
                </div>
                
                <?php if ($result['error']): ?>
                <div class="bg-red-50 border border-red-200 rounded-lg p-3 mb-4">
                    <div class="text-sm text-red-700">
                        <strong>Errore:</strong> <?php echo e($result['error']); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($result['is_json'] && !empty($result['decoded']['data'])): ?>
                <div class="mt-4">
                    <div class="text-xs text-gray-500 uppercase tracking-wide mb-2">Response Data (preview)</div>
                    <div class="json-preview"><?php echo e(json_encode($result['decoded']['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></div>
                </div>
                <?php elseif (!$result['is_json'] && !empty($result['response'])): ?>
                <div class="mt-4">
                    <div class="text-xs text-gray-500 uppercase tracking-wide mb-2">Raw Response</div>
                    <div class="json-preview"><?php echo e(substr($result['response'], 0, 1000)); ?></div>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Summary -->
        <div class="bg-white rounded-xl shadow-sm p-6 mt-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">📊 Riepilogo</h2>
            <?php
            $totalTests = count($endpoints);
            $successTests = 0;
            $jsonValidTests = 0;
            foreach ($endpoints as $name => $queryString) {
                $result = makeRequest($baseUrl . $queryString);
                if ($result['success'] === true) $successTests++;
                if ($result['is_json']) $jsonValidTests++;
            }
            $successRate = $totalTests > 0 ? round(($successTests / $totalTests) * 100) : 0;
            ?>
            <div class="grid grid-cols-4 gap-4">
                <div class="bg-gray-50 rounded-lg p-4 text-center">
                    <div class="text-3xl font-bold text-gray-900"><?php echo $totalTests; ?></div>
                    <div class="text-sm text-gray-500">Test totali</div>
                </div>
                <div class="bg-green-50 rounded-lg p-4 text-center">
                    <div class="text-3xl font-bold text-green-600"><?php echo $successTests; ?></div>
                    <div class="text-sm text-green-600">Success</div>
                </div>
                <div class="bg-blue-50 rounded-lg p-4 text-center">
                    <div class="text-3xl font-bold text-blue-600"><?php echo $jsonValidTests; ?></div>
                    <div class="text-sm text-blue-600">JSON Validi</div>
                </div>
                <div class="bg-purple-50 rounded-lg p-4 text-center">
                    <div class="text-3xl font-bold text-purple-600"><?php echo $successRate; ?>%</div>
                    <div class="text-sm text-purple-600">Success Rate</div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center text-gray-400 text-sm mt-8 pb-8">
            Eterea Gestionale - Test API Report
        </div>
    </div>
</body>
</html>
