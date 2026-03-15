<?php
/**
 * Eterea Gestionale
 * API Clienti
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($method) {
    case 'GET':
        if ($action === 'detail' && isset($_GET['id'])) {
            getCliente($_GET['id']);
        } elseif ($action === 'list') {
            listClienti();
        } elseif ($action === 'search') {
            searchClienti();
        } elseif ($action === 'timeline' && isset($_GET['id'])) {
            getTimelineCliente($_GET['id']);
        } elseif ($action === 'timeline_generale') {
            getTimelineGenerale();
        } else {
            jsonResponse(false, null, 'Azione non valida');
        }
        break;
        
    case 'POST':
        // Verifica CSRF token per tutte le operazioni state-changing
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (empty($csrfToken) || !verifyCsrfToken($csrfToken)) {
            jsonResponse(false, null, 'Token CSRF non valido');
            break;
        }
        
        if ($action === 'create') {
            createCliente();
        } elseif ($action === 'update' && isset($_POST['id'])) {
            updateCliente($_POST['id']);
        } elseif ($action === 'delete' && isset($_POST['id'])) {
            deleteCliente($_POST['id']);
        } else {
            jsonResponse(false, null, 'Azione non valida');
        }
        break;
        
    default:
        jsonResponse(false, null, 'Metodo non consentito');
}

/**
 * Lista clienti
 */
function listClienti() {
    global $pdo;
    
    try {
        $where = [];
        $params = [];
        
        // Filtro tipo
        if (!empty($_GET['tipo'])) {
            $where[] = "tipo = ?";
            $params[] = $_GET['tipo'];
        }
        
        // Ricerca
        if (!empty($_GET['search'])) {
            $where[] = "(ragione_sociale LIKE ? OR email LIKE ? OR piva_cf LIKE ?)";
            $params[] = "%{$_GET['search']}%";
            $params[] = "%{$_GET['search']}%";
            $params[] = "%{$_GET['search']}%";
        }
        
        $sql = "
            SELECT c.id, c.ragione_sociale, c.tipo, c.piva_cf, c.email, c.telefono, c.cellulare, 
                   c.indirizzo, c.citta, c.cap, c.provincia, c.logo_path,
                   c.pec, c.sito_web, c.instagram, c.facebook, c.linkedin, c.note,
                   COUNT(p.id) as num_progetti,
                   SUM(CASE WHEN p.stato_progetto NOT IN ('consegnato','archiviato') THEN 1 ELSE 0 END) as progetti_attivi
            FROM clienti c
            LEFT JOIN progetti p ON c.id = p.cliente_id
        ";
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        $sql .= " GROUP BY c.id ORDER BY c.ragione_sociale ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $clienti = $stmt->fetchAll();
        
        jsonResponse(true, $clienti);
        
    } catch (PDOException $e) {
        error_log("Errore lista clienti: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento clienti');
    }
}

/**
 * Ricerca clienti (per autocomplete)
 */
function searchClienti() {
    global $pdo;
    
    $q = $_GET['q'] ?? '';
    if (strlen($q) < 2) {
        jsonResponse(true, []);
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, ragione_sociale, email, telefono
            FROM clienti
            WHERE ragione_sociale LIKE ? OR email LIKE ?
            ORDER BY ragione_sociale ASC
            LIMIT 10
        ");
        $stmt->execute(["%{$q}%", "%{$q}%"]);
        $clienti = $stmt->fetchAll();
        
        jsonResponse(true, $clienti);
        
    } catch (PDOException $e) {
        error_log("Errore ricerca clienti: " . $e->getMessage());
        jsonResponse(false, null, 'Errore ricerca');
    }
}

/**
 * Dettaglio cliente
 */
function getCliente($id) {
    global $pdo;
    
    try {
        // Cliente
        $stmt = $pdo->prepare("SELECT * FROM clienti WHERE id = ?");
        $stmt->execute([$id]);
        $cliente = $stmt->fetch();
        
        if (!$cliente) {
            jsonResponse(false, null, 'Cliente non trovato');
        }
        
        // Progetti
        $stmt = $pdo->prepare("
            SELECT p.*, 
                   COUNT(t.id) as num_task,
                   SUM(CASE WHEN t.stato = 'completato' THEN 1 ELSE 0 END) as task_completati
            FROM progetti p
            LEFT JOIN task t ON p.id = t.progetto_id
            WHERE p.cliente_id = ?
            GROUP BY p.id
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$id]);
        $cliente['progetti'] = $stmt->fetchAll();
        
        // Decodifica JSON
        foreach ($cliente['progetti'] as &$p) {
            $p['tipologie'] = json_decode($p['tipologie'] ?? '[]', true);
            $p['partecipanti'] = json_decode($p['partecipanti'] ?? '[]', true);
        }
        
        jsonResponse(true, $cliente);
        
    } catch (PDOException $e) {
        error_log("Errore dettaglio cliente: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento cliente');
    }
}

/**
 * Crea nuovo cliente
 */
function createCliente() {
    global $pdo;
    
    // Validazione
    $ragioneSociale = trim($_POST['ragione_sociale'] ?? '');
    
    if (empty($ragioneSociale)) {
        jsonResponse(false, null, 'La ragione sociale è obbligatoria');
    }
    
    try {
        $id = generateEntityId('clt');
        
        $stmt = $pdo->prepare("
            INSERT INTO clienti (
                id, ragione_sociale, tipo, piva_cf, indirizzo, citta, cap, provincia,
                telefono, cellulare, email, pec, instagram, facebook, linkedin, sito_web, note, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $id,
            $ragioneSociale,
            $_POST['tipo'] ?? 'Azienda',
            $_POST['piva_cf'] ?? '',
            $_POST['indirizzo'] ?? '',
            $_POST['citta'] ?? '',
            $_POST['cap'] ?? '',
            $_POST['provincia'] ?? '',
            $_POST['telefono'] ?? '',
            $_POST['cellulare'] ?? '',
            $_POST['email'] ?? '',
            $_POST['pec'] ?? '',
            $_POST['instagram'] ?? '',
            $_POST['facebook'] ?? '',
            $_POST['linkedin'] ?? '',
            $_POST['sito_web'] ?? '',
            $_POST['note'] ?? '',
            $_SESSION['user_id']
        ]);
        
        // Gestisci upload logo se presente
        if (!empty($_FILES['logo'])) {
            error_log("Logo upload attempt - error code: " . $_FILES['logo']['error']);
            if ($_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                error_log("Logo file: " . $_FILES['logo']['name'] . " size: " . $_FILES['logo']['size']);
                $upload = uploadFile($_FILES['logo'], 'clienti', ['image/jpeg', 'image/jpg', 'image/pjpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'], 2 * 1024 * 1024);
                if ($upload) {
                    error_log("Logo uploaded successfully to: " . $upload['path']);
                    $stmt = $pdo->prepare("UPDATE clienti SET logo_path = ? WHERE id = ?");
                    $stmt->execute([$upload['path'], $id]);
                } else {
                    error_log("Logo upload failed in uploadFile function");
                }
            } else {
                error_log("Logo upload error code: " . $_FILES['logo']['error']);
            }
        } else {
            error_log("No logo file in \$_FILES");
        }
        
        // Log
        logTimeline($_SESSION['user_id'], 'creato_cliente', 'cliente', $id, "Creato cliente: {$ragioneSociale}");
        
        // Crea notifica per tutti gli utenti
        creaNotifica(
            'cliente',
            'Nuovo Cliente',
            $ragioneSociale,
            'cliente',
            $id,
            $_SESSION['user_id']
        );
        
        jsonResponse(true, ['id' => $id], 'Cliente creato con successo');
        
    } catch (PDOException $e) {
        error_log("Errore creazione cliente: " . $e->getMessage());
        jsonResponse(false, null, 'Errore creazione cliente');
    }
}

/**
 * Aggiorna cliente
 */
function updateCliente($id) {
    global $pdo;
    
    // Verifica esistenza
    $stmt = $pdo->prepare("SELECT id FROM clienti WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        jsonResponse(false, null, 'Cliente non trovato');
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE clienti SET
                ragione_sociale = ?,
                tipo = ?,
                piva_cf = ?,
                indirizzo = ?,
                citta = ?,
                cap = ?,
                provincia = ?,
                telefono = ?,
                cellulare = ?,
                email = ?,
                pec = ?,
                instagram = ?,
                facebook = ?,
                linkedin = ?,
                sito_web = ?,
                note = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $_POST['ragione_sociale'],
            $_POST['tipo'],
            $_POST['piva_cf'] ?? '',
            $_POST['indirizzo'] ?? '',
            $_POST['citta'] ?? '',
            $_POST['cap'] ?? '',
            $_POST['provincia'] ?? '',
            $_POST['telefono'] ?? '',
            $_POST['cellulare'] ?? '',
            $_POST['email'] ?? '',
            $_POST['pec'] ?? '',
            $_POST['instagram'] ?? '',
            $_POST['facebook'] ?? '',
            $_POST['linkedin'] ?? '',
            $_POST['sito_web'] ?? '',
            $_POST['note'] ?? '',
            $id
        ]);
        
        // Gestisci upload logo se presente
        if (!empty($_FILES['logo'])) {
            error_log("Logo upload attempt in update - error code: " . $_FILES['logo']['error']);
            if ($_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                error_log("Logo file: " . $_FILES['logo']['name'] . " size: " . $_FILES['logo']['size']);
                $upload = uploadFile($_FILES['logo'], 'clienti', ['image/jpeg', 'image/jpg', 'image/pjpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'], 2 * 1024 * 1024);
                if ($upload) {
                    error_log("Logo uploaded successfully to: " . $upload['path']);
                    // Elimina logo precedente se esiste
                    $stmt = $pdo->prepare("SELECT logo_path FROM clienti WHERE id = ?");
                    $stmt->execute([$id]);
                    $old = $stmt->fetch();
                    if ($old && $old['logo_path'] && file_exists(UPLOAD_PATH . $old['logo_path'])) {
                        unlink(UPLOAD_PATH . $old['logo_path']);
                    }
                    
                    $stmt = $pdo->prepare("UPDATE clienti SET logo_path = ? WHERE id = ?");
                    $stmt->execute([$upload['path'], $id]);
                } else {
                    error_log("Logo upload failed in uploadFile function");
                }
            } else {
                error_log("Logo upload error code: " . $_FILES['logo']['error']);
            }
        } else {
            error_log("No logo file in \$_FILES during update");
        }
        
        // Log
        logTimeline($_SESSION['user_id'], 'aggiornato_cliente', 'cliente', $id, "Aggiornato cliente: {$_POST['ragione_sociale']}");
        
        jsonResponse(true, ['id' => $id], 'Cliente aggiornato con successo');
        
    } catch (PDOException $e) {
        error_log("Errore aggiornamento cliente: " . $e->getMessage());
        jsonResponse(false, null, 'Errore aggiornamento cliente');
    }
}

/**
 * Elimina cliente
 */
function deleteCliente($id) {
    global $pdo;
    
    try {
        // Verifica esistenza
        $stmt = $pdo->prepare("SELECT ragione_sociale, logo_path FROM clienti WHERE id = ?");
        $stmt->execute([$id]);
        $cliente = $stmt->fetch();
        
        if (!$cliente) {
            jsonResponse(false, null, 'Cliente non trovato');
        }
        
        // Verifica se ha progetti
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM progetti WHERE cliente_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            jsonResponse(false, null, 'Non è possibile eliminare un cliente con progetti associati');
        }
        
        // Elimina logo se presente
        if ($cliente['logo_path'] && file_exists(UPLOAD_PATH . $cliente['logo_path'])) {
            unlink(UPLOAD_PATH . $cliente['logo_path']);
        }
        
        // Elimina
        $stmt = $pdo->prepare("DELETE FROM clienti WHERE id = ?");
        $stmt->execute([$id]);
        
        // Log
        logTimeline($_SESSION['user_id'], 'eliminato_cliente', 'cliente', $id, "Eliminato cliente: {$cliente['ragione_sociale']}");
        
        jsonResponse(true, null, 'Cliente eliminato con successo');
        
    } catch (PDOException $e) {
        error_log("Errore eliminazione cliente: " . $e->getMessage());
        jsonResponse(false, null, 'Errore eliminazione cliente');
    }
}

/**
 * Timeline cliente - statistiche e storico
 */
function getTimelineCliente($id) {
    global $pdo;
    
    try {
        // Cliente base
        $stmt = $pdo->prepare("SELECT id, ragione_sociale, logo_path, tipo, created_at FROM clienti WHERE id = ?");
        $stmt->execute([$id]);
        $cliente = $stmt->fetch();
        
        if (!$cliente) {
            jsonResponse(false, null, 'Cliente non trovato');
        }
        
        // Progetti con dettagli
        $stmt = $pdo->prepare("
            SELECT p.id, p.titolo, p.stato_progetto, p.prezzo_totale, p.stato_pagamento,
                   p.created_at as data_creazione, p.data_consegna_effettiva,
                   COUNT(t.id) as num_task
            FROM progetti p
            LEFT JOIN task t ON p.id = t.progetto_id
            WHERE p.cliente_id = ?
            GROUP BY p.id
            ORDER BY p.created_at ASC
        ");
        $stmt->execute([$id]);
        $progetti = $stmt->fetchAll();
        
        // Preventivi associati
        $stmt = $pdo->prepare("
            SELECT ps.id, ps.numero, ps.totale, ps.created_at, ps.stato, ps.file_path
            FROM preventivi_salvati ps
            WHERE ps.cliente_id = ? OR (
                ps.cliente_nome = (SELECT ragione_sociale FROM clienti WHERE id = ?)
            )
            ORDER BY ps.created_at DESC
        ");
        $stmt->execute([$id, $id]);
        $preventivi = $stmt->fetchAll();
        
        // Calcola statistiche
        $progettiCompletati = 0;
        $progettiInCorso = 0;
        $totaleSpeso = 0;
        $totalePagato = 0;
        $progettiTimeline = [];
        
        foreach ($progetti as $p) {
            $prezzo = floatval($p['prezzo_totale'] ?? 0);
            
            if (in_array($p['stato_progetto'], ['consegnato', 'archiviato', 'completato'])) {
                $progettiCompletati++;
                $totaleSpeso += $prezzo;
                
                if ($p['stato_pagamento'] === 'pagamento_completato') {
                    $totalePagato += $prezzo;
                }
            } else {
                $progettiInCorso++;
            }
            
            $progettiTimeline[] = [
                'id' => $p['id'],
                'titolo' => $p['titolo'],
                'stato' => $p['stato_progetto'],
                'prezzo' => $prezzo,
                'stato_pagamento' => $p['stato_pagamento'],
                'data' => $p['data_creazione'],
                'num_task' => $p['num_task']
            ];
        }
        
        // Calcola tempo da cliente
        $dataCreazione = new DateTime($cliente['created_at']);
        $oggi = new DateTime();
        $diff = $dataCreazione->diff($oggi);
        
        $tempoDaCliente = '';
        if ($diff->y > 0) {
            $tempoDaCliente = $diff->y . ' ' . ($diff->y == 1 ? 'anno' : 'anni');
            if ($diff->m > 0) {
                $tempoDaCliente .= ' e ' . $diff->m . ' ' . ($diff->m == 1 ? 'mese' : 'mesi');
            }
        } elseif ($diff->m > 0) {
            $tempoDaCliente = $diff->m . ' ' . ($diff->m == 1 ? 'mese' : 'mesi');
            if ($diff->d > 0) {
                $tempoDaCliente .= ' e ' . $diff->d . ' ' . ($diff->d == 1 ? 'giorno' : 'giorni');
            }
        } else {
            $tempoDaCliente = $diff->d . ' ' . ($diff->d == 1 ? 'giorno' : 'giorni');
        }
        
        // Timeline events (progetti + preventivi ordinati per data)
        $events = [];
        
        // Data inserimento cliente
        $events[] = [
            'tipo' => 'cliente_creato',
            'data' => $cliente['created_at'],
            'titolo' => 'Cliente aggiunto al sistema',
            'descrizione' => $cliente['ragione_sociale'] . ' registrato come ' . $cliente['tipo'],
            'icona' => 'user-plus',
            'colore' => 'emerald'
        ];
        
        // Progetti nella timeline
        foreach ($progetti as $p) {
            $events[] = [
                'tipo' => 'progetto_creato',
                'data' => $p['data_creazione'],
                'titolo' => 'Nuovo progetto: ' . $p['titolo'],
                'descrizione' => 'Stato: ' . ucfirst(str_replace('_', ' ', $p['stato_progetto'])) . ' - €' . number_format($p['prezzo_totale'], 2, ',', '.'),
                'icona' => 'folder',
                'colore' => 'cyan',
                'progetto_id' => $p['id']
            ];
            
            if ($p['data_consegna_effettiva']) {
                $events[] = [
                    'tipo' => 'progetto_consegnato',
                    'data' => $p['data_consegna_effettiva'],
                    'titolo' => 'Progetto consegnato: ' . $p['titolo'],
                    'descrizione' => 'Progetto completato e consegnato al cliente',
                    'icona' => 'check-circle',
                    'colore' => 'green',
                    'progetto_id' => $p['id']
                ];
            }
        }
        
        // Preventivi nella timeline
        foreach ($preventivi as $prev) {
            $events[] = [
                'tipo' => 'preventivo_creato',
                'data' => $prev['created_at'],
                'titolo' => 'Preventivo ' . $prev['numero'],
                'descrizione' => 'Totale: €' . number_format($prev['totale'], 2, ',', '.') . ' - Stato: ' . ucfirst($prev['stato'] ?? 'Bozza'),
                'icona' => 'document-text',
                'colore' => 'amber',
                'preventivo_id' => $prev['id']
            ];
        }
        
        // Ordina eventi per data
        usort($events, function($a, $b) {
            return strtotime($b['data']) - strtotime($a['data']);
        });
        
        $timeline = [
            'cliente' => [
                'id' => $cliente['id'],
                'ragione_sociale' => $cliente['ragione_sociale'],
                'logo_path' => $cliente['logo_path'],
                'tipo' => $cliente['tipo'],
                'data_inserimento' => $cliente['created_at']
            ],
            'statistiche' => [
                'tempo_da_cliente' => $tempoDaCliente,
                'progetti_totali' => count($progetti),
                'progetti_completati' => $progettiCompletati,
                'progetti_in_corso' => $progettiInCorso,
                'totale_speso' => $totaleSpeso,
                'totale_pagato' => $totalePagato,
                'totale_da_riscuotere' => $totaleSpeso - $totalePagato,
                'numero_preventivi' => count($preventivi)
            ],
            'progetti' => $progettiTimeline,
            'preventivi' => $preventivi,
            'timeline' => $events
        ];
        
        jsonResponse(true, $timeline);
        
    } catch (PDOException $e) {
        error_log("Errore timeline cliente: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento timeline');
    }
}

/**
 * Timeline generale - tutti i progetti e clienti
 */
function getTimelineGenerale() {
    global $pdo;
    
    // Assicura che l'output sia sempre JSON
    header('Content-Type: application/json');
    
    try {
        // Clienti totali
        $stmt = $pdo->query("SELECT COUNT(*) as totale FROM clienti");
        if ($stmt === false) {
            throw new Exception('Query clienti fallita');
        }
        $clientiTotali = $stmt->fetch()['totale'];
        
        // Clienti per mese (ultimi 12 mesi) - compatibile MySQL 5.7
        $clientiPerMese = [];
        try {
            $stmt = $pdo->query("
                SELECT CONCAT(YEAR(created_at), '-', LPAD(MONTH(created_at), 2, '0')) as mese, COUNT(*) as num
                FROM clienti
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY YEAR(created_at), MONTH(created_at)
                ORDER BY YEAR(created_at) ASC, MONTH(created_at) ASC
            ");
            if ($stmt !== false) {
                $clientiPerMese = $stmt->fetchAll();
            }
        } catch (Exception $e) {
            $clientiPerMese = [];
        }
        
        // Progetti con dettagli
        $progetti = [];
        try {
            $stmt = $pdo->query("
                SELECT p.id, p.titolo, p.stato_progetto, p.prezzo_totale, p.stato_pagamento,
                       p.created_at as data_creazione, p.data_consegna_effettiva,
                       c.id as cliente_id, c.ragione_sociale as cliente_nome, c.logo_path as cliente_logo
                FROM progetti p
                LEFT JOIN clienti c ON p.cliente_id = c.id
                ORDER BY p.created_at DESC
                LIMIT 100
            ");
            if ($stmt !== false) {
                $progetti = $stmt->fetchAll();
            }
        } catch (Exception $e) {
            $progetti = [];
        }
        
        // Preventivi - verifica esistenza tabella
        $preventivi = [];
        try {
            // Verifica se tabella esiste
            $stmt = $pdo->query("SHOW TABLES LIKE 'preventivi_salvati'");
            if ($stmt !== false && $stmt->rowCount() > 0) {
                $stmt = $pdo->query("
                    SELECT ps.id, ps.numero, ps.totale, ps.created_at, ps.stato, ps.file_path,
                           c.id as cliente_id, c.ragione_sociale as cliente_nome
                    FROM preventivi_salvati ps
                    LEFT JOIN clienti c ON ps.cliente_id = c.id
                    ORDER BY ps.created_at DESC
                    LIMIT 50
                ");
                if ($stmt !== false) {
                    $preventivi = $stmt->fetchAll();
                }
            }
        } catch (Exception $e) {
            $preventivi = [];
        }
        
        // Calcola statistiche
        $progettiCompletati = 0;
        $progettiInCorso = 0;
        $progettiDaIniziare = 0;
        $totaleFatturato = 0;
        $totalePagato = 0;
        $totaleDaRiscuotere = 0;
        
        foreach ($progetti as $p) {
            $prezzo = floatval($p['prezzo_totale'] ?? 0);
            
            if (in_array($p['stato_progetto'], ['consegnato', 'archiviato', 'completato'])) {
                $progettiCompletati++;
                $totaleFatturato += $prezzo;
                
                if ($p['stato_pagamento'] === 'pagamento_completato') {
                    $totalePagato += $prezzo;
                } else {
                    $totaleDaRiscuotere += $prezzo;
                }
            } elseif ($p['stato_progetto'] === 'in_corso') {
                $progettiInCorso++;
            } elseif ($p['stato_progetto'] === 'da_iniziare') {
                $progettiDaIniziare++;
            }
        }
        
        // Timeline events (progetti + preventivi + clienti ordinati per data)
        $events = [];
        
        // Progetti nella timeline
        foreach ($progetti as $p) {
            $events[] = [
                'tipo' => 'progetto_' . $p['stato_progetto'],
                'data' => $p['data_creazione'],
                'titolo' => $p['titolo'],
                'descrizione' => ($p['cliente_nome'] ? $p['cliente_nome'] . ' - ' : '') . 
                                '€' . number_format($p['prezzo_totale'], 2, ',', '.') . ' - ' . 
                                ucfirst(str_replace('_', ' ', $p['stato_progetto'])),
                'icona' => 'folder',
                'colore' => $p['stato_progetto'] === 'consegnato' ? 'green' : 
                            ($p['stato_progetto'] === 'in_corso' ? 'cyan' : 'slate'),
                'progetto_id' => $p['id'],
                'cliente_nome' => $p['cliente_nome'],
                'cliente_logo' => $p['cliente_logo']
            ];
            
            if ($p['data_consegna_effettiva']) {
                $events[] = [
                    'tipo' => 'progetto_consegnato',
                    'data' => $p['data_consegna_effettiva'],
                    'titolo' => '✓ ' . $p['titolo'] . ' consegnato',
                    'descrizione' => 'Progetto completato per ' . ($p['cliente_nome'] ?: 'Cliente'),
                    'icona' => 'check-circle',
                    'colore' => 'emerald',
                    'progetto_id' => $p['id'],
                    'cliente_nome' => $p['cliente_nome'],
                    'cliente_logo' => $p['cliente_logo']
                ];
            }
        }
        
        // Preventivi nella timeline
        foreach ($preventivi as $prev) {
            $events[] = [
                'tipo' => 'preventivo_creato',
                'data' => $prev['created_at'],
                'titolo' => 'Preventivo ' . $prev['numero'],
                'descrizione' => ($prev['cliente_nome'] ? $prev['cliente_nome'] . ' - ' : '') .
                                '€' . number_format($prev['totale'], 2, ',', '.'),
                'icona' => 'document-text',
                'colore' => 'amber',
                'preventivo_id' => $prev['id'],
                'cliente_nome' => $prev['cliente_nome']
            ];
        }
        
        // Ordina eventi per data (più recenti prima)
        usort($events, function($a, $b) {
            return strtotime($b['data']) - strtotime($a['data']);
        });
        
        // Prendi solo i primi 50 eventi
        $events = array_slice($events, 0, 50);
        
        $timeline = [
            'statistiche' => [
                'clienti_totali' => $clientiTotali,
                'progetti_totali' => count($progetti),
                'progetti_completati' => $progettiCompletati,
                'progetti_in_corso' => $progettiInCorso,
                'progetti_da_iniziare' => $progettiDaIniziare,
                'totale_fatturato' => $totaleFatturato,
                'totale_pagato' => $totalePagato,
                'totale_da_riscuotere' => $totaleDaRiscuotere,
                'numero_preventivi' => count($preventivi),
                'clienti_per_mese' => $clientiPerMese
            ],
            'timeline' => $events
        ];
        
        jsonResponse(true, $timeline);
        
    } catch (PDOException $e) {
        error_log("Errore timeline generale PDO: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Errore database: ' . $e->getMessage()]);
        exit;
    } catch (Exception $e) {
        error_log("Errore timeline generale: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Errore: ' . $e->getMessage()]);
        exit;
    } catch (Throwable $e) {
        error_log("Errore critico timeline generale: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Errore critico: ' . $e->getMessage()]);
        exit;
    }
}
