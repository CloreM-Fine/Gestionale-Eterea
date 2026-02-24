<?php
/**
 * API Contabilità Mensile
 */

// Abilita error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Header JSON
header('Content-Type: application/json; charset=utf-8');

// Configurazione sessione PRIMA di session_start
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
           (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
ini_set('session.cookie_secure', $isHttps ? 1 : 0);

// AVVIA SESSIONE
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Verifica autenticazione
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non autenticato']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'riepilogo':
        getRiepilogoMensile();
        break;
    case 'salva':
        salvaRiepilogoMensile();
        break;
    case 'cronologia':
        getCronologiaMensile();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Azione non valida']);
}

function getRiepilogoMensile() {
    global $pdo;
    
    $mese = intval($_GET['mese'] ?? date('n'));
    $anno = intval($_GET['anno'] ?? date('Y'));
    
    $dataInizio = sprintf('%04d-%02d-01', $anno, $mese);
    $dataFine = date('Y-m-t', strtotime($dataInizio));
    
    try {
        $saldoIniziale = getSaldoIniziale($mese, $anno);
        
        $stmt = $pdo->prepare("
            SELECT SUM(prezzo_totale) as totale, COUNT(*) as numero
            FROM progetti 
            WHERE stato_progetto = 'completato' 
            AND DATE(data_consegna_effettiva) BETWEEN :inizio AND :fine
        ");
        $stmt->execute([':inizio' => $dataInizio, ':fine' => $dataFine]);
        $risultato = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $totaleEntrate = floatval($risultato['totale'] ?? 0);
        $numeroProgetti = intval($risultato['numero'] ?? 0);
        $saldoFinale = $saldoIniziale + $totaleEntrate;
        
        $cronologia = getCronologiaProgetti($dataInizio, $dataFine);
        
        salvaRiepilogoAutomatico($mese, $anno, $saldoIniziale, $totaleEntrate, $saldoFinale, $numeroProgetti);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'mese' => $mese,
                'anno' => $anno,
                'periodo' => sprintf('%02d/%04d', $mese, $anno),
                'saldo_iniziale' => $saldoIniziale,
                'totale_entrate' => $totaleEntrate,
                'numero_progetti' => $numeroProgetti,
                'saldo_finale' => $saldoFinale,
                'cronologia' => $cronologia
            ]
        ]);
        
    } catch (PDOException $e) {
        error_log("Errore contabilità: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Errore database']);
    } catch (Throwable $e) {
        error_log("Errore generico: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Errore: ' . $e->getMessage()]);
    }
}

function getSaldoIniziale($mese, $anno) {
    global $pdo;
    
    try {
        $mesePrecedente = $mese - 1;
        $annoPrecedente = $anno;
        if ($mesePrecedente < 1) {
            $mesePrecedente = 12;
            $annoPrecedente--;
        }
        
        $stmt = $pdo->prepare("
            SELECT saldo_finale 
            FROM contabilita_mensile 
            WHERE mese = :mese AND anno = :anno 
            LIMIT 1
        ");
        $stmt->execute([':mese' => $mesePrecedente, ':anno' => $annoPrecedente]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['saldo_finale'] !== null) {
            return floatval($result['saldo_finale']);
        }
    } catch (PDOException $e) {
        error_log("Errore getSaldoIniziale: " . $e->getMessage());
    }
    
    return 0;
}

function getCronologiaProgetti($dataInizio, $dataFine) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, titolo as nome, prezzo_totale as importo, data_consegna_effettiva as data
            FROM progetti 
            WHERE stato_progetto = 'completato' 
            AND DATE(data_consegna_effettiva) BETWEEN :inizio AND :fine
            ORDER BY data_consegna_effettiva DESC
        ");
        $stmt->execute([':inizio' => $dataInizio, ':fine' => $dataFine]);
        $progetti = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $cronologia = [];
        foreach ($progetti as $progetto) {
            $cronologia[] = [
                'id' => $progetto['id'],
                'tipo' => 'Progetto: ' . $progetto['nome'],
                'importo' => floatval($progetto['importo']),
                'data' => $progetto['data']
            ];
        }
        
        return $cronologia;
    } catch (PDOException $e) {
        error_log("Errore getCronologiaProgetti: " . $e->getMessage());
        return [];
    }
}

function salvaRiepilogoAutomatico($mese, $anno, $saldoIniziale, $totaleEntrate, $saldoFinale, $numeroProgetti) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT id FROM contabilita_mensile 
            WHERE mese = :mese AND anno = :anno 
            LIMIT 1
        ");
        $stmt->execute([':mese' => $mese, ':anno' => $anno]);
        $esistente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($esistente) {
            $stmt = $pdo->prepare("
                UPDATE contabilita_mensile 
                SET saldo_iniziale = :saldo_iniziale,
                    totale_entrate = :totale_entrate,
                    saldo_finale = :saldo_finale,
                    numero_progetti = :numero_progetti
                WHERE id = :id
            ");
            $stmt->execute([
                ':id' => $esistente['id'],
                ':saldo_iniziale' => $saldoIniziale,
                ':totale_entrate' => $totaleEntrate,
                ':saldo_finale' => $saldoFinale,
                ':numero_progetti' => $numeroProgetti
            ]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO contabilita_mensile 
                (mese, anno, saldo_iniziale, totale_entrate, saldo_finale, numero_progetti, creato_il)
                VALUES (:mese, :anno, :saldo_iniziale, :totale_entrate, :saldo_finale, :numero_progetti, NOW())
            ");
            $stmt->execute([
                ':mese' => $mese,
                ':anno' => $anno,
                ':saldo_iniziale' => $saldoIniziale,
                ':totale_entrate' => $totaleEntrate,
                ':saldo_finale' => $saldoFinale,
                ':numero_progetti' => $numeroProgetti
            ]);
        }
    } catch (PDOException $e) {
        error_log("Errore salvataggio: " . $e->getMessage());
    }
}

function salvaRiepilogoMensile() {
    global $pdo;
    
    if (!isAdmin()) {
        echo json_encode(['success' => false, 'message' => 'Permesso negato']);
        return;
    }
    
    $mese = intval($_POST['mese'] ?? 0);
    $anno = intval($_POST['anno'] ?? 0);
    $saldoIniziale = floatval($_POST['saldo_iniziale'] ?? 0);
    $totaleEntrate = floatval($_POST['totale_entrate'] ?? 0);
    $saldoFinale = floatval($_POST['saldo_finale'] ?? 0);
    $numeroProgetti = intval($_POST['numero_progetti'] ?? 0);
    
    if ($mese < 1 || $mese > 12 || $anno < 2020) {
        echo json_encode(['success' => false, 'message' => 'Dati non validi']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO contabilita_mensile 
            (mese, anno, saldo_iniziale, totale_entrate, saldo_finale, numero_progetti, creato_il)
            VALUES (:mese, :anno, :saldo_iniziale, :totale_entrate, :saldo_finale, :numero_progetti, NOW())
            ON DUPLICATE KEY UPDATE
                saldo_iniziale = :saldo_iniziale,
                totale_entrate = :totale_entrate,
                saldo_finale = :saldo_finale,
                numero_progetti = :numero_progetti
        ");
        
        $stmt->execute([
            ':mese' => $mese,
            ':anno' => $anno,
            ':saldo_iniziale' => $saldoIniziale,
            ':totale_entrate' => $totaleEntrate,
            ':saldo_finale' => $saldoFinale,
            ':numero_progetti' => $numeroProgetti
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Riepilogo salvato']);
        
    } catch (PDOException $e) {
        error_log("Errore salvataggio: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Errore database']);
    }
}

function getCronologiaMensile() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("
            SELECT * FROM contabilita_mensile 
            ORDER BY anno DESC, mese DESC
        ");
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $records]);
        
    } catch (PDOException $e) {
        error_log("Errore cronologia: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Errore database']);
    }
}
