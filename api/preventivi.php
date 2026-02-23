<?php
/**
 * Eterea Gestionale
 * API Preventivi
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($method) {
    case 'GET':
        if ($action === 'list') {
            getPreventivi();
        } elseif ($action === 'categorie') {
            getCategorie();
        } elseif ($action === 'list_preventivi_salvati') {
            listPreventiviSalvati();
        } elseif ($action === 'view_preventivo' && !empty($_GET['id'])) {
            viewPreventivoSalvato($_GET['id']);
        } else {
            jsonResponse(false, null, 'Azione non valida');
        }
        break;
        
    case 'POST':
        if ($action === 'save_voce') {
            saveVoce();
        } elseif ($action === 'delete_voce' && isset($_POST['id'])) {
            deleteVoce($_POST['id']);
        } elseif ($action === 'save_categoria') {
            saveCategoria();
        } elseif ($action === 'delete_categoria' && isset($_POST['id'])) {
            deleteCategoria($_POST['id']);
        } elseif ($action === 'genera_preventivo') {
            generaPreventivo();
        } elseif ($action === 'salva_preventivo') {
            salvaPreventivoGestionale();
        } elseif ($action === 'associa_progetto') {
            associaPreventivoAProgetto();
        } elseif ($action === 'delete_preventivo' && !empty($_POST['id'])) {
            deletePreventivoSalvato($_POST['id']);
        } else {
            jsonResponse(false, null, 'Azione non valida');
        }
        break;
        
    default:
        jsonResponse(false, null, 'Metodo non consentito');
}

/**
 * Ottieni listino completo con categorie e voci
 */
function getPreventivi(): void {
    global $pdo;
    
    try {
        // Ottieni categorie ordinate
        $stmt = $pdo->query("SELECT * FROM listino_categorie ORDER BY ordine ASC, nome ASC");
        $categorie = $stmt->fetchAll();
        
        // Per ogni categoria, ottieni le voci
        foreach ($categorie as &$cat) {
            $stmt = $pdo->prepare("
                SELECT * FROM listino_voci 
                WHERE categoria_id = ? AND attivo = TRUE 
                ORDER BY ordine ASC, tipo_servizio ASC
            ");
            $stmt->execute([$cat['id']]);
            $cat['voci'] = $stmt->fetchAll();
        }
        
        jsonResponse(true, $categorie);
    } catch (PDOException $e) {
        error_log("Errore get preventivi: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento preventivi');
    }
}

/**
 * Ottieni solo categorie
 */
function getCategorie(): void {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT * FROM listino_categorie ORDER BY ordine ASC, nome ASC");
        $categorie = $stmt->fetchAll();
        jsonResponse(true, $categorie);
    } catch (PDOException $e) {
        error_log("Errore get categorie: " . $e->getMessage());
        jsonResponse(false, null, 'Errore');
    }
}

/**
 * Salva voce (crea o aggiorna)
 */
function saveVoce(): void {
    global $pdo;
    
    $id = $_POST['id'] ?? null;
    $categoriaId = $_POST['categoria_id'] ?? '';
    $tipoServizio = trim($_POST['tipo_servizio'] ?? '');
    $descrizione = trim($_POST['descrizione'] ?? '');
    $prezzo = floatval($_POST['prezzo'] ?? 0);
    $sconto = intval($_POST['sconto_percentuale'] ?? 0);
    
    if (empty($categoriaId) || empty($tipoServizio)) {
        jsonResponse(false, null, 'Categoria e tipo servizio sono obbligatori');
        return;
    }
    
    try {
        if ($id) {
            // Aggiorna
            $stmt = $pdo->prepare("
                UPDATE listino_voci 
                SET categoria_id = ?, tipo_servizio = ?, descrizione = ?, 
                    prezzo = ?, sconto_percentuale = ?
                WHERE id = ?
            ");
            $stmt->execute([$categoriaId, $tipoServizio, $descrizione, $prezzo, $sconto, $id]);
            jsonResponse(true, ['id' => $id], 'Voce aggiornata');
        } else {
            // Crea nuova
            $stmt = $pdo->prepare("
                INSERT INTO listino_voci (categoria_id, tipo_servizio, descrizione, prezzo, sconto_percentuale, ordine)
                VALUES (?, ?, ?, ?, ?, 999)
            ");
            $stmt->execute([$categoriaId, $tipoServizio, $descrizione, $prezzo, $sconto]);
            jsonResponse(true, ['id' => $pdo->lastInsertId()], 'Voce creata');
        }
    } catch (PDOException $e) {
        error_log("Errore save voce: " . $e->getMessage());
        jsonResponse(false, null, 'Errore salvataggio');
    }
}

/**
 * Elimina voce
 */
function deleteVoce(int $id): void {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM listino_voci WHERE id = ?");
        $stmt->execute([$id]);
        jsonResponse(true, null, 'Voce eliminata');
    } catch (PDOException $e) {
        error_log("Errore delete voce: " . $e->getMessage());
        jsonResponse(false, null, 'Errore eliminazione');
    }
}

/**
 * Salva categoria
 */
function saveCategoria(): void {
    global $pdo;
    
    $id = $_POST['id'] ?? null;
    $nome = trim($_POST['nome'] ?? '');
    
    if (empty($nome)) {
        jsonResponse(false, null, 'Nome categoria obbligatorio');
        return;
    }
    
    try {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE listino_categorie SET nome = ? WHERE id = ?");
            $stmt->execute([$nome, $id]);
            jsonResponse(true, ['id' => $id], 'Categoria aggiornata');
        } else {
            $stmt = $pdo->prepare("INSERT INTO listino_categorie (nome, ordine) VALUES (?, 999)");
            $stmt->execute([$nome]);
            jsonResponse(true, ['id' => $pdo->lastInsertId()], 'Categoria creata');
        }
    } catch (PDOException $e) {
        error_log("Errore save categoria: " . $e->getMessage());
        jsonResponse(false, null, 'Errore salvataggio');
    }
}

/**
 * Elimina categoria (e tutte le sue voci)
 */
function deleteCategoria(int $id): void {
    global $pdo;
    
    try {
        // Le voci verranno eliminate automaticamente per ON DELETE CASCADE
        $stmt = $pdo->prepare("DELETE FROM listino_categorie WHERE id = ?");
        $stmt->execute([$id]);
        jsonResponse(true, null, 'Categoria e voci eliminate');
    } catch (PDOException $e) {
        error_log("Errore delete categoria: " . $e->getMessage());
        jsonResponse(false, null, 'Errore eliminazione');
    }
}

/**
 * Genera preventivo PDF
 */
function generaPreventivo(): void {
    global $pdo;
    
    $vociSelezionate = json_decode($_POST['voci'] ?? '[]', true);
    $clienteNome = trim($_POST['cliente_nome'] ?? 'Cliente');
    $preventivoNum = trim($_POST['preventivo_num'] ?? 'PREV-' . date('Y') . '-001');
    $note = trim($_POST['note'] ?? '');
    $scontoGlobale = floatval($_POST['sconto_globale'] ?? 0);
    $dataScadenza = trim($_POST['data_scadenza'] ?? '');
    
    if (empty($vociSelezionate)) {
        jsonResponse(false, null, 'Nessuna voce selezionata');
        return;
    }
    
    // Recupera dettagli voci
    $ids = array_map('intval', array_column($vociSelezionate, 'id'));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    try {
        $stmt = $pdo->prepare("
            SELECT v.*, c.nome as categoria_nome
            FROM listino_voci v
            JOIN listino_categorie c ON v.categoria_id = c.id
            WHERE v.id IN ($placeholders)
        ");
        $stmt->execute($ids);
        $voci = $stmt->fetchAll();
        
        // Mappa le quantità personalizzate
        $quantitaMap = [];
        foreach ($vociSelezionate as $v) {
            $quantitaMap[$v['id']] = $v['quantita'] ?? 1;
        }
        
        // Calcola totali
        $subtotale = 0;
        foreach ($voci as &$voce) {
            $qty = $quantitaMap[$voce['id']] ?? 1;
            $prezzoUnitario = floatval($voce['prezzo']);
            $scontoPerc = intval($voce['sconto_percentuale']);
            
            $prezzoScontato = $prezzoUnitario * (1 - $scontoPerc / 100);
            $totaleVoce = $prezzoScontato * $qty;
            
            $voce['quantita'] = $qty;
            $voce['prezzo_scontato'] = $prezzoScontato;
            $voce['totale'] = $totaleVoce;
            
            $subtotale += $totaleVoce;
        }
        
        // Applica sconto globale
        $totaleScontato = $subtotale * (1 - $scontoGlobale / 100);
        
        // Genera HTML per PDF
        $html = generaHTMLPreventivo($voci, $clienteNome, $preventivoNum, $note, $scontoGlobale, $subtotale, $totaleScontato, $dataScadenza);
        
        // Salva HTML temporaneo
        $filename = 'preventivo_' . time() . '.html';
        $filepath = __DIR__ . '/../assets/temp/' . $filename;
        
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        file_put_contents($filepath, $html);
        
        jsonResponse(true, [
            'html_url' => 'assets/temp/' . $filename,
            'preview_html' => $html
        ], 'Preventivo generato');
        
    } catch (PDOException $e) {
        error_log("Errore genera preventivo: " . $e->getMessage());
        jsonResponse(false, null, 'Errore generazione preventivo');
    }
}

/**
 * Recupera i dati dell'azienda dal database
 */
function getDatiAzienda(): array {
    global $pdo;
    
    try {
        $chiavi = [
            'azienda_ragione_sociale',
            'azienda_indirizzo',
            'azienda_cap',
            'azienda_citta',
            'azienda_provincia',
            'azienda_piva',
            'azienda_cf',
            'azienda_email',
            'azienda_telefono',
            'azienda_pec',
            'azienda_sdi',
            'azienda_logo'
        ];
        
        $dati = [];
        foreach ($chiavi as $chiave) {
            $stmt = $pdo->prepare("SELECT valore FROM impostazioni WHERE chiave = ?");
            $stmt->execute([$chiave]);
            $dati[str_replace('azienda_', '', $chiave)] = $stmt->fetchColumn() ?: '';
        }
        
        // Default se non configurati
        if (empty($dati['ragione_sociale'])) {
            $dati['ragione_sociale'] = 'Eterea Studio';
        }
        
        return $dati;
    } catch (PDOException $e) {
        error_log("Errore get dati azienda: " . $e->getMessage());
        return ['ragione_sociale' => 'Eterea Studio'];
    }
}

/**
 * Genera HTML del preventivo
 */
function generaHTMLPreventivo(array $voci, string $cliente, string $numero, string $note, float $scontoGlobale, float $subtotale, float $totale, string $dataScadenza = ''): string {
    $data = date('d/m/Y');
    $validita = $dataScadenza ? date('d/m/Y', strtotime($dataScadenza)) : date('d/m/Y', strtotime('+30 days'));
    $clienteEsc = htmlspecialchars($cliente);
    $numeroEsc = htmlspecialchars($numero);
    $noteEsc = $note ? nl2br(htmlspecialchars($note)) : '';
    
    // Recupera dati azienda
    $datiAzienda = getDatiAzienda();
    
    // Costruisci indirizzo completo
    $indirizzoCompleto = '';
    if ($datiAzienda['indirizzo']) {
        $indirizzoCompleto = $datiAzienda['indirizzo'];
        if ($datiAzienda['cap'] || $datiAzienda['citta']) {
            $indirizzoCompleto .= ', ';
        }
    }
    if ($datiAzienda['cap']) {
        $indirizzoCompleto .= $datiAzienda['cap'] . ' ';
    }
    if ($datiAzienda['citta']) {
        $indirizzoCompleto .= $datiAzienda['citta'];
    }
    if ($datiAzienda['provincia']) {
        $indirizzoCompleto .= ' (' . $datiAzienda['provincia'] . ')';
    }
    
    // Costruisci riga contatti
    $contatti = [];
    if ($datiAzienda['telefono']) $contatti[] = 'Tel: ' . $datiAzienda['telefono'];
    if ($datiAzienda['email']) $contatti[] = $datiAzienda['email'];
    if ($datiAzienda['pec']) $contatti[] = 'PEC: ' . $datiAzienda['pec'];
    $contattiStr = implode(' | ', $contatti);
    
    // Logo
    $logoHtml = '';
    if (!empty($datiAzienda['logo'])) {
        $logoPath = __DIR__ . '/../assets/uploads/logo_azienda/' . $datiAzienda['logo'];
        if (file_exists($logoPath)) {
            $logoData = base64_encode(file_get_contents($logoPath));
            $logoExt = pathinfo($datiAzienda['logo'], PATHINFO_EXTENSION);
            $mimeType = ($logoExt === 'svg') ? 'image/svg+xml' : 'image/' . $logoExt;
            $logoHtml = '<img src="data:' . $mimeType . ';base64,' . $logoData . '" style="max-height:60px;max-width:150px;object-fit:contain;" alt="Logo">';
        }
    }
    
    $ragioneSociale = htmlspecialchars($datiAzienda['ragione_sociale']);
    $piva = $datiAzienda['piva'] ? 'P.IVA ' . htmlspecialchars($datiAzienda['piva']) : '';
    $cf = $datiAzienda['cf'] ? 'CF ' . htmlspecialchars($datiAzienda['cf']) : '';
    $sdi = $datiAzienda['sdi'] ? 'SDI: ' . htmlspecialchars($datiAzienda['sdi']) : '';
    
    // Raggruppa per categoria
    $grouped = [];
    foreach ($voci as $v) {
        $cat = $v['categoria_nome'];
        if (!isset($grouped[$cat])) $grouped[$cat] = [];
        $grouped[$cat][] = $v;
    }
    
    $righe = '';
    foreach ($grouped as $categoria => $items) {
        $catEsc = htmlspecialchars($categoria);
        $righe .= "<tr class='categoria'><td colspan='6'><strong>{$catEsc}</strong></td></tr>";
        foreach ($items as $item) {
            $qty = $item['quantita'];
            $tipoEsc = htmlspecialchars($item['tipo_servizio']);
            $descEsc = htmlspecialchars($item['descrizione'] ?? '');
            $prezzoForm = number_format($item['prezzo'], 2, ',', '.');
            $sconto = $item['sconto_percentuale'] > 0 ? "-{$item['sconto_percentuale']}%" : '-';
            $totaleForm = number_format($item['totale'], 2, ',', '.');
            
            $righe .= "<tr><td>{$tipoEsc}</td><td>{$descEsc}</td><td style='text-align:center'>{$qty}</td><td style='text-align:right'>€ {$prezzoForm}</td><td style='text-align:center'>{$sconto}</td><td style='text-align:right'><strong>€ {$totaleForm}</strong></td></tr>";
        }
    }
    
    $subtotaleForm = number_format($subtotale, 2, ',', '.');
    $totaleFormStr = number_format($totale, 2, ',', '.');
    $scontoGlobaleTxt = '';
    if ($scontoGlobale > 0) {
        $scontoVal = number_format($subtotale - $totale, 2, ',', '.');
        $scontoGlobaleTxt = "<tr><td colspan='5' style='text-align:right'>Sconto globale: -{$scontoGlobale}%</td><td style='text-align:right'>-€ {$scontoVal}</td></tr>";
    }
    
    $noteHtml = $noteEsc ? "<div class='note'><strong>Note:</strong><br>{$noteEsc}</div>" : '';
    
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Preventivo {$numero}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 12px; 
            line-height: 1.5; 
            color: #1e293b;
            padding: 40px;
        }
        .header { 
            display: flex; 
            justify-content: space-between; 
            align-items: flex-start;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid #0891b2;
        }
        .logo { 
            display: flex; 
            align-items: center; 
            gap: 12px;
        }
        .logo-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #0891b2, #06b6d4);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            font-weight: 700;
        }
        .logo-text h1 { font-size: 24px; font-weight: 700; color: #0891b2; }
        .logo-text p { font-size: 11px; color: #64748b; }
        .doc-info { text-align: right; }
        .doc-info h2 { 
            font-size: 18px; 
            color: #0891b2; 
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .doc-info p { color: #64748b; font-size: 11px; margin: 2px 0; }
        
        .client-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            gap: 30px;
        }
        .client-box, .validita-box {
            flex: 1;
            padding: 20px;
            background: #f8fafc;
            border-radius: 8px;
        }
        .client-box h3, .validita-box h3 {
            font-size: 11px;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        .client-box .nome { font-size: 16px; font-weight: 600; color: #1e293b; }
        .validita-box p { color: #475569; font-size: 12px; }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 20px 0;
            font-size: 11px;
        }
        th { 
            background: #f1f5f9; 
            padding: 12px 8px; 
            text-align: left; 
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #cbd5e1;
        }
        td { 
            padding: 12px 8px; 
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }
        tr.categoria td {
            background: #f8fafc;
            padding-top: 16px;
            border-bottom: 1px solid #cbd5e1;
        }
        tr.categoria td strong {
            color: #0891b2;
            font-size: 12px;
        }
        
        .totals { 
            margin-top: 20px;
            margin-left: auto;
            width: 350px;
        }
        .totals table { margin: 0; }
        .totals td { padding: 8px; border: none; }
        .totals tr:last-child { 
            background: linear-gradient(135deg, #0891b2, #06b6d4);
            color: white;
            font-size: 14px;
            font-weight: 700;
        }
        .totals tr:last-child td { padding: 15px 12px; }
        
        .note {
            margin-top: 30px;
            padding: 15px;
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            border-radius: 0 8px 8px 0;
            font-size: 11px;
            color: #92400e;
        }
        
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            color: #94a3b8;
            font-size: 10px;
        }
        .footer strong { color: #64748b; }
        
        .condizioni {
            margin-top: 30px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 8px;
            font-size: 10px;
            color: #64748b;
        }
        .condizioni h4 {
            color: #475569;
            margin-bottom: 10px;
            font-size: 11px;
        }
        .condizioni ul { margin-left: 15px; }
        .condizioni li { margin: 5px 0; }
        
        @media print {
            body { padding: 20px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            {$logoHtml}
            <div class="logo-text">
                <h1>{$ragioneSociale}</h1>
                <p>{$indirizzoCompleto}</p>
            </div>
        </div>
        <div class="doc-info">
            <h2>Preventivo</h2>
            <p><strong>N.:</strong> {$numero}</p>
            <p><strong>Data:</strong> {$data}</p>
        </div>
    </div>
    
    <div class="client-section">
        <div class="client-box">
            <h3>Preventivo per</h3>
            <div class="nome">{$clienteEsc}</div>
        </div>
        <div class="validita-box">
            <h3>Validità preventivo</h3>
            <p>Questo preventivo è valido fino al <strong>{$validita}</strong></p>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th style="width:22%">Servizio</th>
                <th style="width:35%">Descrizione</th>
                <th style="width:8%;text-align:center">Q.tà</th>
                <th style="width:13%;text-align:right">Prezzo</th>
                <th style="width:10%;text-align:center">Sconto</th>
                <th style="width:12%;text-align:right">Totale</th>
            </tr>
        </thead>
        <tbody>
            {$righe}
        </tbody>
    </table>
    
    <div class="totals">
        <table>
            <tr>
                <td style="text-align:right"><strong>Subtotale:</strong></td>
                <td style="text-align:right;width:120px">€ {$subtotaleForm}</td>
            </tr>
            {$scontoGlobaleTxt}
            <tr>
                <td style="text-align:right"><strong>TOTALE:</strong></td>
                <td style="text-align:right"><strong>€ {$totaleForm}</strong></td>
            </tr>
        </table>
    </div>
    
    {$noteHtml}
    
    <div class="condizioni">
        <h4>Condizioni Generali</h4>
        <ul>
            <li>Le modalità di pagamento saranno concordate in fase di accettazione del preventivo</li>
            <li>I tempi di consegna indicati sono da intendersi a partire dalla ricezione di tutti i materiali necessari</li>
            <li>Eventuali modifiche successive all'approvazione del progetto finale potrebbero comportare costi aggiuntivi</li>
            <li>I prezzi indicati sono da intendersi IVA esclusa</li>
        </ul>
    </div>
    
    <div class="footer">
        <p><strong>{$ragioneSociale}</strong> | {$indirizzoCompleto} | {$piva} {$cf}</p>
        <p>{$contattiStr}</p>
        <p style="margin-top:10px;font-style:italic;">Grazie per la fiducia accordataci!</p>
    </div>
    
    <div class="no-print" style="margin-top:30px;text-align:center;padding:20px;background:#f8fafc;border-radius:8px;">
        <button onclick="window.print()" style="padding:12px 24px;background:#0891b2;color:white;border:none;border-radius:6px;cursor:pointer;font-size:14px;">
            🖨️ Stampa / Salva PDF
        </button>
        <p style="margin-top:10px;font-size:11px;color:#64748b;">
            Clicca per stampare e scegli "Salva come PDF" nel menu a discesa
        </p>
    </div>
</body>
</html>
HTML;
}


/**
 * Salva il preventivo nel gestionale come documento
 */
function salvaPreventivoGestionale(): void {
    global $pdo;
    
    // Recupera dati dal POST
    $numero = $_POST['numero'] ?? '';
    $clienteId = $_POST['cliente_id'] ?? null;
    $clienteNome = $_POST['cliente_nome'] ?? '';
    $dataScadenza = $_POST['data_scadenza'] ?? null;
    $scontoGlobale = floatval($_POST['sconto_globale'] ?? 0);
    $note = $_POST['note'] ?? '';
    $serviziJson = $_POST['servizi'] ?? '[]';
    $subtotale = floatval($_POST['subtotale'] ?? 0);
    $totale = floatval($_POST['totale'] ?? 0);
    
    if (empty($clienteNome)) {
        jsonResponse(false, null, 'Il nome cliente è obbligatorio');
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Salva nel database
        $stmt = $pdo->prepare("
            INSERT INTO preventivi_salvati 
            (numero, cliente_id, cliente_nome, data_validita, sconto_globale, note, servizi_json, subtotale, totale, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $numero,
            $clienteId,
            $clienteNome,
            $dataScadenza ?: null,
            $scontoGlobale,
            $note,
            $serviziJson,
            $subtotale,
            $totale,
            $_SESSION['user_id']
        ]);
        
        $preventivoId = $pdo->lastInsertId();
        
        // Genera il file HTML del preventivo
        $servizi = json_decode($serviziJson, true);
        $html = generaHTMLPreventivoSalvato($servizi, $clienteNome, $numero, $note, $scontoGlobale, $subtotale, $totale, $dataScadenza);
        
        // Salva il file
        $filename = 'preventivo_' . $preventivoId . '_' . time() . '.html';
        $uploadDir = __DIR__ . '/../assets/uploads/preventivi/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $filepath = $uploadDir . $filename;
        file_put_contents($filepath, $html);
        
        // Aggiorna il record con il path del file
        $stmt = $pdo->prepare("UPDATE preventivi_salvati SET file_path = ? WHERE id = ?");
        $stmt->execute([$filename, $preventivoId]);
        
        $pdo->commit();
        
        // Log
        logTimeline($_SESSION['user_id'], 'salvato_preventivo', 'preventivo', $preventivoId, "Salvato preventivo {$numero} per {$clienteNome}");
        
        jsonResponse(true, [
            'id' => $preventivoId,
            'file_path' => $filename,
            'cliente_nome' => $clienteNome
        ], 'Preventivo salvato nel gestionale con successo');
        
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Errore salva preventivo: " . $e->getMessage());
        jsonResponse(false, null, 'Errore durante il salvataggio del preventivo');
    }
}

/**
 * Genera HTML del preventivo salvato (versione semplificata)
 */
function generaHTMLPreventivoSalvato(array $voci, string $cliente, string $numero, string $note, float $scontoGlobale, float $subtotale, float $totale, string $dataScadenza = ''): string {
    $data = date('d/m/Y');
    $validita = $dataScadenza ? date('d/m/Y', strtotime($dataScadenza)) : date('d/m/Y', strtotime('+30 days'));
    $clienteEsc = htmlspecialchars($cliente);
    $numeroEsc = htmlspecialchars($numero);
    $noteEsc = $note ? nl2br(htmlspecialchars($note)) : '';
    
    $righe = '';
    foreach ($voci as $v) {
        $nome = htmlspecialchars($v['tipo_servizio']);
        $desc = htmlspecialchars($v['descrizione'] ?? '');
        $prezzo = number_format($v['prezzo'], 2, ',', '.');
        $sconto = $v['sconto_percentuale'] ?? 0;
        $prezzoFinale = $v['prezzo'] * (1 - $sconto / 100);
        $prezzoFinaleFmt = number_format($prezzoFinale, 2, ',', '.');
        
        $scontoHtml = $sconto > 0 ? "<br><small style='color:#dc2626;'>-{$sconto}%</small>" : '';
        
        $righe .= "
        <tr>
            <td><strong>{$nome}</strong><br><small style='color:#64748b;'>{$desc}</small></td>
            <td style='text-align:right;'>€ {$prezzo}{$scontoHtml}</td>
        </tr>
        ";
    }
    
    $scontoHtml = $scontoGlobale > 0 ? "
    <div style='background:#fef3c7;padding:15px;border-radius:6px;margin:15px 0;'>
        <strong>Sconto applicato: {$scontoGlobale}%</strong>
    </div>
    " : '';
    
    $noteHtml = $note ? "
    <div style='margin:20px 0;padding:15px;background:#f8fafc;border-radius:6px;'>
        <h4 style='margin:0 0 10px;color:#475569;'>Note</h4>
        <p style='margin:0;color:#64748b;'>{$noteEsc}</p>
    </div>
    " : '';
    
    return <<<HTML
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Preventivo {$numeroEsc}</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; color: #333; }
        .header { text-align: center; border-bottom: 3px solid #0891b2; padding-bottom: 20px; margin-bottom: 30px; }
        .header h1 { color: #0891b2; margin: 0; font-size: 28px; }
        .header p { color: #64748b; margin: 5px 0; }
        .info-box { background: #f8fafc; padding: 20px; border-radius: 8px; margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #f1f5f9; padding: 12px; text-align: left; font-weight: 600; }
        td { padding: 12px; border-bottom: 1px solid #e2e8f0; }
        .totale { text-align: right; font-size: 20px; font-weight: bold; color: #0891b2; margin-top: 20px; }
        @media print { .no-print { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <div class="header">
        <h1>PREVENTIVO</h1>
        <p>N. {$numeroEsc} - del {$data}</p>
    </div>
    
    <div class="info-box">
        <strong>Cliente:</strong> {$clienteEsc}<br>
        <strong>Valido fino al:</strong> {$validita}
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Servizio</th>
                <th style="text-align:right;">Prezzo</th>
            </tr>
        </thead>
        <tbody>
            {$righe}
        </tbody>
    </table>
    
    {$scontoHtml}
    {$noteHtml}
    
    <div class="totale">
        TOTALE: € {$totale}
    </div>
    
    <div class="no-print" style="margin-top:30px;text-align:center;padding:20px;background:#f8fafc;border-radius:8px;">
        <button onclick="window.print()" style="padding:12px 24px;background:#0891b2;color:white;border:none;border-radius:6px;cursor:pointer;">
            🖨️ Stampa / Salva PDF
        </button>
    </div>
</body>
</html>
HTML;
}


/**
 * Lista dei preventivi salvati
 */
function listPreventiviSalvati(): void {
    global $pdo;
    
    try {
        // Query senza JOIN per evitare problemi di collation
        $stmt = $pdo->query("
            SELECT * FROM preventivi_salvati 
            ORDER BY created_at DESC
            LIMIT 50
        ");
        $preventivi = $stmt->fetchAll();
        
        jsonResponse(true, $preventivi);
    } catch (PDOException $e) {
        error_log("Errore lista preventivi salvati: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento preventivi');
    }
}

/**
 * Visualizza un preventivo salvato
 */
function viewPreventivoSalvato(int $id): void {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT ps.*, c.ragione_sociale as cliente_ragione_sociale, u.nome as creato_da_nome
            FROM preventivi_salvati ps
            LEFT JOIN clienti c ON ps.cliente_id = c.id
            LEFT JOIN utenti u ON ps.created_by = u.id
            WHERE ps.id = ?
        ");
        $stmt->execute([$id]);
        $preventivo = $stmt->fetch();
        
        if (!$preventivo) {
            jsonResponse(false, null, 'Preventivo non trovato');
        }
        
        jsonResponse(true, $preventivo);
    } catch (PDOException $e) {
        error_log("Errore view preventivo: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento preventivo');
    }
}


/**
 * Associa un preventivo salvato a un progetto
 */
function associaPreventivoAProgetto(): void {
    global $pdo;
    
    $preventivoId = $_POST['preventivo_id'] ?? '';
    $progettoId = $_POST['progetto_id'] ?? '';
    
    if (empty($preventivoId) || empty($progettoId)) {
        jsonResponse(false, null, 'Preventivo e progetto sono obbligatori');
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Verifica che il preventivo esista
        $stmt = $pdo->prepare("SELECT * FROM preventivi_salvati WHERE id = ?");
        $stmt->execute([$preventivoId]);
        $preventivo = $stmt->fetch();
        
        if (!$preventivo) {
            jsonResponse(false, null, 'Preventivo non trovato');
            return;
        }
        
        // Verifica che il progetto esista
        $stmt = $pdo->prepare("SELECT * FROM progetti WHERE id = ?");
        $stmt->execute([$progettoId]);
        $progetto = $stmt->fetch();
        
        if (!$progetto) {
            jsonResponse(false, null, 'Progetto non trovato');
            return;
        }
        
        // Aggiorna il preventivo con il riferimento al progetto
        // Aggiungiamo una colonna progetto_id se non esiste
        try {
            $stmt = $pdo->prepare("
                ALTER TABLE preventivi_salvati ADD COLUMN IF NOT EXISTS progetto_id VARCHAR(20) NULL AFTER cliente_id
            ");
            $stmt->execute();
        } catch (PDOException $e) {
            // Ignora errore se la colonna esiste già
        }
        
        $stmt = $pdo->prepare("UPDATE preventivi_salvati SET progetto_id = ? WHERE id = ?");
        $stmt->execute([$progettoId, $preventivoId]);
        
        // Crea una task nel progetto con il riferimento al preventivo
        $servizi = json_decode($preventivo['servizi_json'] ?? '[]', true);
        $numServizi = count($servizi);
        
        $taskTitolo = "Preventivo " . $preventivo['numero'];
        $taskDescrizione = "Preventivo approvato per " . $preventivo['cliente_nome'] . 
                          "\nTotale: €" . number_format($preventivo['totale'], 2) . 
                          "\nServizi: " . $numServizi;
        
        // Genera ID task
        $taskId = 'tsk_' . uniqid();
        
        $stmt = $pdo->prepare("
            INSERT INTO task (id, progetto_id, titolo, descrizione, stato, priorita, created_by, created_at)
            VALUES (?, ?, ?, ?, 'da_fare', 'media', ?, NOW())
        ");
        $stmt->execute([
            $taskId,
            $progettoId,
            $taskTitolo,
            $taskDescrizione,
            $_SESSION['user_id']
        ]);
        
        $pdo->commit();
        
        // Log
        logTimeline($_SESSION['user_id'], 'preventivo_associato', 'progetto', $progettoId, 
            "Preventivo {$preventivo['numero']} associato al progetto {$progetto['titolo']}");
        
        jsonResponse(true, null, 'Preventivo associato al progetto con successo');
        
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Errore associazione preventivo: " . $e->getMessage());
        jsonResponse(false, null, 'Errore durante l\'associazione');
    }
}


/**
 * Elimina un preventivo salvato
 */
function deletePreventivoSalvato(int $id): void {
    global $pdo;
    
    try {
        // Recupera info per il log
        $stmt = $pdo->prepare("SELECT numero, file_path FROM preventivi_salvati WHERE id = ?");
        $stmt->execute([$id]);
        $preventivo = $stmt->fetch();
        
        if (!$preventivo) {
            jsonResponse(false, null, 'Preventivo non trovato');
            return;
        }
        
        // Elimina il file se esiste
        if ($preventivo['file_path']) {
            $filepath = __DIR__ . '/../assets/uploads/preventivi/' . $preventivo['file_path'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }
        
        // Elimina dal database
        $stmt = $pdo->prepare("DELETE FROM preventivi_salvati WHERE id = ?");
        $stmt->execute([$id]);
        
        // Log
        logTimeline($_SESSION['user_id'], 'eliminato_preventivo', 'preventivo', $id, 
            "Eliminato preventivo {$preventivo['numero']}");
        
        jsonResponse(true, null, 'Preventivo eliminato con successo');
        
    } catch (PDOException $e) {
        error_log("Errore eliminazione preventivo: " . $e->getMessage());
        jsonResponse(false, null, 'Errore durante l\'eliminazione');
    }
}
