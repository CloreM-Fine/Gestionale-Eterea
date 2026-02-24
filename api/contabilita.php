<?php
/**
 * API Contabilità Mensile
 * Gestisce riepilogo, cronologia e rollover mensile
 */

header('Content-Type: application/json; charset=utf-8');

// Include config
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

/**
 * Ottiene il riepilogo mensile con saldo iniziale, entrate e saldo finale
 */
function getRiepilogoMensile() {
    global $pdo;
    
    $mese = intval($_GET['mese'] ?? date('n'));
    $anno = intval($_GET['anno'] ?? date('Y'));
    
    // Calcola date inizio/fine mese
    $dataInizio = sprintf('%04d-%02d-01', $anno, $mese);
    $dataFine = date('Y-m-t', strtotime($dataInizio));
    
    try {
        // 1. Saldo iniziale = saldo finale mese precedente (o cassa aziendale se primo mese)
        $saldoIniziale = getSaldoIniziale($mese, $anno);
        
        // 2. Entrate del mese: somma degli importi dei progetti consegnati
        $stmt = $pdo->prepare("
            SELECT SUM(prezzo_totale) as totale, COUNT(*) as numero
            FROM progetti 
            WHERE stato = 'consegnato' 
            AND DATE(data_consegna) BETWEEN :inizio AND :fine
        ");
        $stmt->execute([':inizio' => $dataInizio, ':fine' => $dataFine]);
        $risultato = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $totaleEntrate = floatval($risultato['totale'] ?? 0);
        $numeroProgetti = intval($risultato['numero'] ?? 0);
        
        // 3. Saldo finale = saldo iniziale + entrate
        $saldoFinale = $saldoIniziale + $totaleEntrate;
        
        // 4. Cronologia del mese (progetti consegnati)
        $cronologia = getCronologiaProgetti($dataInizio, $dataFine);
        
        // Salva automaticamente il riepilogo per lo storico
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
        error_log("Errore contabilità mensile: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Errore database: ' . $e->getMessage()]);
    } catch (Throwable $e) {
        error_log("Errore generico contabilità: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Errore: ' . $e->getMessage()]);
    }
}

/**
 * Ottiene il saldo iniziale per un mese
 * - Se esiste storico del mese precedente, usa il saldo finale
 * - Altrimenti usa il saldo della cassa aziendale
 */
function getSaldoIniziale($mese, $anno) {
    global $pdo;
    
    try {
        // Calcola mese precedente
        $mesePrecedente = $mese - 1;
        $annoPrecedente = $anno;
        if ($mesePrecedente < 1) {
            $mesePrecedente = 12;
            $annoPrecedente--;
        }
        
        // Cerca saldo finale del mese precedente
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
    
    // Se non c'è storico o c'è errore, usa il saldo della cassa aziendale
    return getSaldoCassaAziendale();
}

/**
 * Ottiene il saldo attuale della cassa aziendale
 */
function getSaldoCassaAziendale() {
    global $pdo;
    
    try {
        // Cerca wallet "cassa" o simile
        $stmt = $pdo->query("
            SELECT COALESCE(SUM(saldo), 0) as saldo 
            FROM wallets 
            WHERE nome LIKE '%cassa%' OR nome LIKE '%aziendale%' OR nome LIKE '%generale%'
            LIMIT 1
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['saldo'] > 0) {
            return floatval($result['saldo']);
        }
        
        // Altrimenti somma tutti i wallet degli utenti
        $stmt = $pdo->query("
            SELECT COALESCE(SUM(saldo), 0) as saldo_totale 
            FROM wallets w
            JOIN utenti u ON w.user_id = u.id
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return floatval($result['saldo_totale'] ?? 0);
    } catch (PDOException $e) {
        error_log("Errore getSaldoCassaAziendale: " . $e->getMessage());
        return 0;
    }
}

/**
 * Ottiene la cronologia dei progetti consegnati nel periodo
 */
function getCronologiaProgetti($dataInizio, $dataFine) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                id, 
                nome, 
                prezzo_totale as importo, 
                data_consegna as data
            FROM progetti 
            WHERE stato = 'consegnato' 
            AND DATE(data_consegna) BETWEEN :inizio AND :fine
            ORDER BY data_consegna DESC
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

/**
 * Salva automaticamente il riepilogo mensile per lo storico
 */
function salvaRiepilogoAutomatico($mese, $anno, $saldoIniziale, $totaleEntrate, $saldoFinale, $numeroProgetti) {
    global $pdo;
    
    try {
        // Verifica se esiste già un record per questo mese
        $stmt = $pdo->prepare("
            SELECT id FROM contabilita_mensile 
            WHERE mese = :mese AND anno = :anno 
            LIMIT 1
        ");
        $stmt->execute([':mese' => $mese, ':anno' => $anno]);
        $esistente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($esistente) {
            // Aggiorna record esistente
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
            // Crea nuovo record
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
        error_log("Errore salvataggio contabilità: " . $e->getMessage());
    }
}

/**
 * Salva manualmente un riepilogo mensile
 */
function salvaRiepilogoMensile() {
    // Solo admin possono salvare manualmente
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
        global $pdo;
        
        $stmt = $pdo->prepare("
            INSERT INTO contabilita_mensile 
            (mese, anno, saldo_iniziale, totale_entrate, saldo_finale, numero_progetti, creato_il, aggiornato_il)
            VALUES (:mese, :anno, :saldo_iniziale, :totale_entrate, :saldo_finale, :numero_progetti, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                saldo_iniziale = :saldo_iniziale,
                totale_entrate = :totale_entrate,
                saldo_finale = :saldo_finale,
                numero_progetti = :numero_progetti,
                aggiornato_il = NOW()
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
        error_log("Errore salvataggio manuale: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Errore database']);
    }
}

/**
 * Ottiene la cronologia storica di tutti i mesi
 */
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
        echo json_encode(['success' => false, 'message' => 'Errore database']);
    }
}

