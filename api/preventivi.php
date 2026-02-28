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
    $frequenza = intval($_POST['frequenza'] ?? 1);
    
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
                    prezzo = ?, sconto_percentuale = ?, frequenza = ?
                WHERE id = ?
            ");
            $stmt->execute([$categoriaId, $tipoServizio, $descrizione, $prezzo, $sconto, $frequenza, $id]);
            jsonResponse(true, ['id' => $id], 'Voce aggiornata');
        } else {
            // Crea nuova
            $stmt = $pdo->prepare("
                INSERT INTO listino_voci (categoria_id, tipo_servizio, descrizione, prezzo, sconto_percentuale, frequenza, ordine)
                VALUES (?, ?, ?, ?, ?, ?, 999)
            ");
            $stmt->execute([$categoriaId, $tipoServizio, $descrizione, $prezzo, $sconto, $frequenza]);
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
    $clienteId = $_POST['cliente_id'] ?? null;
    $clienteNome = trim($_POST['cliente_nome'] ?? 'Cliente');
    $preventivoNum = trim($_POST['preventivo_num'] ?? 'PREV-' . date('Y') . '-001');
    $note = trim($_POST['note'] ?? '');
    $tempiConsegna = trim($_POST['tempi_consegna'] ?? '');
    $nonInclude = trim($_POST['non_include'] ?? '');
    $scontoGlobale = floatval($_POST['sconto_globale'] ?? 0);
    $dataScadenza = trim($_POST['data_scadenza'] ?? '');
    $frequenza = intval($_POST['frequenza'] ?? 1);
    $mostraBurocrazia = filter_var($_POST['mostra_burocrazia'] ?? 'true', FILTER_VALIDATE_BOOLEAN);
    
    if (empty($vociSelezionate)) {
        jsonResponse(false, null, 'Nessuna voce selezionata');
        return;
    }
    
    // Recupera dati cliente se presente
    $datiCliente = [];
    if (!empty($clienteId)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM clienti WHERE id = ?");
            $stmt->execute([$clienteId]);
            $datiCliente = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Errore recupero dati cliente: " . $e->getMessage());
        }
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
        
        // Mappa le quantità, prezzi e sconti personalizzati
        $quantitaMap = [];
        $prezzoMap = [];
        $scontoSingoloMap = [];
        foreach ($vociSelezionate as $v) {
            $quantitaMap[$v['id']] = $v['quantita'] ?? 1;
            // Converte il prezzo in float (il frontend invia già un numero o stringa con . come decimale)
            $prezzoRaw = $v['prezzo'] ?? null;
            if ($prezzoRaw !== null) {
                // Se è una stringa con virgola (formato italiano), converti in punto
                if (is_string($prezzoRaw) && strpos($prezzoRaw, ',') !== false) {
                    $prezzoRaw = str_replace(',', '.', $prezzoRaw);
                }
                $prezzoMap[$v['id']] = floatval($prezzoRaw);
            } else {
                $prezzoMap[$v['id']] = null;
            }
            $scontoSingoloMap[$v['id']] = $v['sconto_singolo'] ?? 0;
        }
        
        // Calcola totali
        $subtotale = 0;
        foreach ($voci as &$voce) {
            $qty = $quantitaMap[$voce['id']] ?? 1;
            $scontoSingolo = floatval($scontoSingoloMap[$voce['id']] ?? 0);
            $prezzoDatabase = floatval($voce['prezzo']);
            $scontoListino = intval($voce['sconto_percentuale']);
            
            // Verifica se il prezzo è stato modificato dall'utente
            $prezzoModificato = isset($prezzoMap[$voce['id']]) && $prezzoMap[$voce['id']] !== null;
            
            if ($prezzoModificato) {
                // Se il prezzo è stato modificato, usa quel prezzo direttamente 
                // (l'utente ha già considerato eventuali sconti listino)
                $prezzoUnitario = $prezzoMap[$voce['id']];
                $prezzoScontato = $prezzoUnitario * (1 - $scontoSingolo / 100);
            } else {
                // Se il prezzo NON è stato modificato, applica lo sconto listino
                $prezzoUnitario = $prezzoDatabase;
                $prezzoConScontoListino = $prezzoUnitario * (1 - $scontoListino / 100);
                $prezzoScontato = $prezzoConScontoListino * (1 - $scontoSingolo / 100);
            }
            
            $totaleVoce = $prezzoScontato * $qty;
            
            $voce['quantita'] = $qty;
            $voce['sconto_singolo'] = $scontoSingolo;
            $voce['prezzo_scontato'] = $prezzoScontato;
            $voce['totale'] = $totaleVoce;
            
            $subtotale += $totaleVoce;
        }
        
        // Applica frequenza al subtotale
        $subtotaleConFrequenza = $subtotale * $frequenza;
        
        // Applica sconto globale sul subtotale con frequenza
        $totaleScontato = $subtotaleConFrequenza * (1 - $scontoGlobale / 100);
        
        // Genera HTML per PDF
        $html = generaHTMLPreventivo($voci, $clienteNome, $preventivoNum, $note, $scontoGlobale, $subtotaleConFrequenza, $totaleScontato, $dataScadenza, $frequenza, $mostraBurocrazia, $tempiConsegna, $nonInclude, $datiCliente);
        
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
            'azienda_sdi'
        ];
        
        // Logo e firma hanno chiavi diverse
        $chiavi[] = 'logo_azienda';
        $chiavi[] = 'firma_azienda';
        
        $dati = [];
        foreach ($chiavi as $chiave) {
            $stmt = $pdo->prepare("SELECT valore FROM impostazioni WHERE chiave = ?");
            $stmt->execute([$chiave]);
            $valore = $stmt->fetchColumn() ?: '';
            // Gestione chiavi con nome diverso
            if ($chiave === 'logo_azienda') {
                $dati['logo'] = $valore;
            } elseif ($chiave === 'firma_azienda') {
                $dati['firma'] = $valore;
            } else {
                $dati[str_replace('azienda_', '', $chiave)] = $valore;
            }
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
 * Recupera il template burocratico di default
 */
function getTemplateBurocraziaDefault(): array {
    global $pdo;
    
    try {
        // Recupera ID template default
        $stmt = $pdo->prepare("SELECT valore FROM impostazioni WHERE chiave = 'preventivo_template_burocrazia_default'");
        $stmt->execute();
        $templateId = $stmt->fetchColumn();
        
        if ($templateId) {
            $stmt = $pdo->prepare("SELECT nome, tipo, contenuto FROM preventivo_template_burocrazia WHERE id = ?");
            $stmt->execute([$templateId]);
            $template = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($template) return $template;
        }
        
        // Fallback: prendi il primo template disponibile
        $stmt = $pdo->query("SELECT nome, tipo, contenuto FROM preventivo_template_burocrazia LIMIT 1");
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($template) return $template;
        
        // Fallback finale
        return [
            'nome' => 'Termini e Condizioni',
            'tipo' => 'generale',
            'contenuto' => 'Ai sensi del D.Lgs. 206/2005 (Codice del Consumo), il cliente viene informato che i prezzi sono espressi in Euro e sono da intendersi IVA esclusa. I pagamenti dovranno essere effettuati secondo le modalità indicate nel presente documento.'
        ];
    } catch (PDOException $e) {
        error_log("Errore get template burocrazia: " . $e->getMessage());
        return [
            'nome' => 'Termini e Condizioni',
            'tipo' => 'generale',
            'contenuto' => 'Ai sensi del D.Lgs. 206/2005 (Codice del Consumo), il cliente viene informato che i prezzi sono espressi in Euro e sono da intendersi IVA esclusa.'
        ];
    }
}

/**
 * Recupera il template condizioni di default
 */
function getTemplateCondizioniDefault(): string {
    global $pdo;
    
    try {
        // Recupera ID template default
        $stmt = $pdo->prepare("SELECT valore FROM impostazioni WHERE chiave = 'preventivo_template_default'");
        $stmt->execute();
        $templateId = $stmt->fetchColumn();
        
        if ($templateId) {
            $stmt = $pdo->prepare("SELECT contenuto FROM preventivo_template_condizioni WHERE id = ?");
            $stmt->execute([$templateId]);
            $contenuto = $stmt->fetchColumn();
            if ($contenuto) return $contenuto;
        }
        
        // Fallback: prendi il primo template disponibile
        $stmt = $pdo->query("SELECT contenuto FROM preventivo_template_condizioni LIMIT 1");
        $contenuto = $stmt->fetchColumn();
        if ($contenuto) return $contenuto;
        
        // Fallback finale: condizioni standard
        return "- Le modalità di pagamento saranno concordate in fase di accettazione del preventivo\n- I tempi di consegna indicati sono da intendersi a partire dalla ricezione di tutti i materiali necessari\n- Eventuali modifiche successive all'approvazione del progetto finale potrebbero comportare costi aggiuntivi\n- I prezzi indicati sono da intendersi IVA esclusa";
    } catch (PDOException $e) {
        error_log("Errore get template condizioni: " . $e->getMessage());
        return "- Le modalità di pagamento saranno concordate in fase di accettazione del preventivo\n- I tempi di consegna indicati sono da intendersi a partire dalla ricezione di tutti i materiali necessari\n- Eventuali modifiche successive all'approvazione del progetto finale potrebbero comportare costi aggiuntivi\n- I prezzi indicati sono da intendersi IVA esclusa";
    }
}

/**
 * Genera HTML del preventivo
 */
function generaHTMLPreventivo(array $voci, string $cliente, string $numero, string $note, float $scontoGlobale, float $subtotale, float $totale, string $dataScadenza = '', int $frequenza = 1, bool $mostraBurocrazia = true, string $tempiConsegna = '', string $nonInclude = '', array $datiCliente = []): string {
    $data = date('d/m/Y');
    $validita = $dataScadenza ? date('d/m/Y', strtotime($dataScadenza)) : date('d/m/Y', strtotime('+30 days'));
    $clienteEsc = htmlspecialchars($cliente);
    $numeroEsc = htmlspecialchars($numero);
    $noteEsc = $note ? nl2br(htmlspecialchars($note)) : '';
    
    // Prepara dettagli cliente
    $clienteDettagli = '';
    if (!empty($datiCliente)) {
        $dettagli = [];
        if (!empty($datiCliente['indirizzo'])) $dettagli[] = htmlspecialchars($datiCliente['indirizzo']);
        if (!empty($datiCliente['cap']) || !empty($datiCliente['citta'])) {
            $dettagli[] = htmlspecialchars(trim($datiCliente['cap'] . ' ' . $datiCliente['citta']));
        }
        if (!empty($datiCliente['piva'])) $dettagli[] = 'P.IVA: ' . htmlspecialchars($datiCliente['piva']);
        elseif (!empty($datiCliente['cf'])) $dettagli[] = 'CF: ' . htmlspecialchars($datiCliente['cf']);
        if (!empty($datiCliente['email'])) $dettagli[] = htmlspecialchars($datiCliente['email']);
        if (!empty($datiCliente['telefono'])) $dettagli[] = 'Tel: ' . htmlspecialchars($datiCliente['telefono']);
        
        if (!empty($dettagli)) {
            $clienteDettagli = '<div class="cliente-dettagli">' . implode(' | ', $dettagli) . '</div>';
        }
    }
    
    // Recupera dati azienda (incluse firma)
    $datiAzienda = getDatiAzienda();
    
    // Prepara firma HTML se presente
    $firmaHtml = '';
    if (!empty($datiAzienda['firma'])) {
        $firmaPath = __DIR__ . '/../assets/uploads/firma_azienda/' . $datiAzienda['firma'];
        if (file_exists($firmaPath)) {
            $firmaData = base64_encode(file_get_contents($firmaPath));
            $firmaExt = pathinfo($datiAzienda['firma'], PATHINFO_EXTENSION);
            $mimeType = ($firmaExt === 'svg') ? 'image/svg+xml' : 'image/' . $firmaExt;
            $firmaHtml = '<img src="data:' . $mimeType . ';base64,' . $firmaData . '" style="max-height:50px;max-width:120px;object-fit:contain;" alt="Firma">';
        }
    }
    
    // Recupera template condizioni
    $templateContenuto = getTemplateCondizioniDefault();
    $condizioniHtml = '';
    if ($templateContenuto) {
        $righe = explode("\n", $templateContenuto);
        foreach ($righe as $riga) {
            $riga = trim($riga);
            if (!empty($riga)) {
                // Se inizia con - o •, rimuovilo e crea li
                if (strpos($riga, '-') === 0 || strpos($riga, '•') === 0) {
                    $riga = trim(substr($riga, 1));
                }
                $condizioniHtml .= '<li>' . htmlspecialchars($riga) . '</li>';
            }
        }
    }
    
    // Costruisci sezione Tempi di consegna e Non include
    $condizioniExtraHtml = '';
    if (!empty($tempiConsegna) || !empty($nonInclude)) {
        $condizioniExtraHtml = '<div class="condizioni-extra">';
        if (!empty($tempiConsegna)) {
            $condizioniExtraHtml .= '
                <div class="condizioni-extra-item">
                    <h4>🕐 Tempi di Consegna</h4>
                    <p>' . htmlspecialchars($tempiConsegna) . '</p>
                </div>';
        }
        if (!empty($nonInclude)) {
            $condizioniExtraHtml .= '
                <div class="condizioni-extra-item">
                    <h4>📋 Non Include</h4>
                    <p>' . htmlspecialchars($nonInclude) . '</p>
                </div>';
        }
        $condizioniExtraHtml .= '</div>';
    }
    
    // Recupera template burocratico (privacy, termini, ecc.)
    $burocraziaHtml = '';
    if ($mostraBurocrazia) {
        $templateBurocrazia = getTemplateBurocraziaDefault();
        if ($templateBurocrazia && !empty($templateBurocrazia['contenuto'])) {
            $titoloBurocrazia = htmlspecialchars($templateBurocrazia['nome']);
            $contenutoBurocrazia = nl2br(htmlspecialchars($templateBurocrazia['contenuto']));
            $burocraziaHtml = <<<BUROCRAZIA
    <div class="burocrazia">
        <h4>{$titoloBurocrazia}</h4>
        <div class="burocrazia-content">{$contenutoBurocrazia}</div>
    </div>
BUROCRAZIA;
        }
    }
    
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
            // Supporta HTML nella descrizione per bullet points
            $descHtml = $item['descrizione'] ?? '';
            if (!empty($descHtml)) {
                // Converte newline in <br> se non c'è già HTML
                if (strpos($descHtml, '<') === false) {
                    $descHtml = nl2br(htmlspecialchars($descHtml));
                } else {
                    // Permette solo tag HTML sicuri
                    $allowedTags = '<br><p><ul><ol><li><strong><em><b><i><span>';
                    $descHtml = strip_tags($descHtml, $allowedTags);
                }
            }
            $prezzoForm = number_format($item['prezzo'], 2, ',', '.');
            
            // Gestione sconti combinati
            $scontoListino = intval($item['sconto_percentuale']);
            $scontoSingolo = floatval($item['sconto_singolo'] ?? 0);
            
            if ($scontoListino > 0 && $scontoSingolo > 0) {
                $sconto = "-{$scontoListino}% + -{$scontoSingolo}%";
            } elseif ($scontoListino > 0) {
                $sconto = "-{$scontoListino}%";
            } elseif ($scontoSingolo > 0) {
                $sconto = "-{$scontoSingolo}%";
            } else {
                $sconto = '-';
            }
            
            $totaleRigaForm = number_format($item['totale'], 2, ',', '.');
            
            $righe .= "<tr><td>{$tipoEsc}</td><td>{$descHtml}</td><td style='text-align:center'>{$qty}</td><td style='text-align:right'>€ {$prezzoForm}</td><td style='text-align:center'>{$sconto}</td><td style='text-align:right'><strong>€ {$totaleRigaForm}</strong></td></tr>";
        }
    }
    
    $subtotaleForm = number_format($subtotale, 2, ',', '.');
    $totaleForm = number_format($totale, 2, ',', '.');
    
    // Riga frequenza se > 1
    $frequenzaTxt = '';
    if ($frequenza > 1) {
        $frequenzaNomi = [
            1 => 'Una tantum',
            2 => 'Settimanale',
            3 => 'Mensile',
            4 => 'Trimestrale',
            5 => 'Semestrale',
            6 => 'Annuale'
        ];
        $freqNome = $frequenzaNomi[$frequenza] ?? 'x' . $frequenza;
        // Calcola il subtotale base (prima della frequenza)
        $subtotaleBase = $subtotale / $frequenza;
        $subtotaleBaseForm = number_format($subtotaleBase, 2, ',', '.');
        $frequenzaTxt = "<tr><td style='text-align:right'>{$freqNome}:</td><td style='text-align:right'>x{$frequenza}</td></tr>";
    }
    
    $scontoGlobaleTxt = '';
    if ($scontoGlobale > 0) {
        $scontoVal = number_format($subtotale * ($scontoGlobale / 100), 2, ',', '.');
        $scontoGlobaleTxt = "<tr><td style='text-align:right'>Sconto globale ({$scontoGlobale}%):</td><td style='text-align:right'>-€ {$scontoVal}</td></tr>";
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
            font-size: 10px; 
            line-height: 1.4; 
            color: #1e293b;
            padding: 30px;
        }
        /* Stili per elenchi in descrizioni */
        ul, ol { 
            margin: 4px 0; 
            padding-left: 16px; 
        }
        ul { list-style-type: disc; }
        ol { list-style-type: decimal; }
        li { 
            margin: 2px 0; 
            padding-left: 4px;
        }
        td ul, td ol {
            margin: 2px 0;
            padding-left: 12px;
        }
        td li {
            font-size: 8px;
            line-height: 1.3;
        }
        .header { 
            display: flex; 
            justify-content: space-between; 
            align-items: flex-start;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid #151e26;
        }
        .logo { 
            display: flex; 
            align-items: center; 
            gap: 12px;
        }
        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #151e26, #151e26);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            font-weight: 700;
        }
        .logo-text h1 { font-size: 18px; font-weight: 700; color: #151e26; }
        .logo-text p { font-size: 9px; color: #64748b; }
        .doc-info { text-align: right; }
        .doc-info h2 { 
            font-size: 14px; 
            color: #151e26; 
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .doc-info p { color: #64748b; font-size: 9px; margin: 2px 0; }
        
        .client-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            gap: 20px;
        }
        .client-box, .validita-box {
            flex: 1;
            padding: 15px;
            background: #f8fafc;
            border-radius: 8px;
        }
        .client-box h3, .validita-box h3 {
            font-size: 9px;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 6px;
            letter-spacing: 0.5px;
        }
        .client-box .nome { font-size: 13px; font-weight: 600; color: #1e293b; }
        .cliente-dettagli { 
            font-size: 9px; 
            color: #64748b; 
            margin-top: 4px;
            line-height: 1.4;
        }
        .validita-box p { color: #475569; font-size: 10px; }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 15px 0;
            font-size: 9px;
        }
        th { 
            background: #f1f5f9; 
            padding: 8px 6px; 
            text-align: left; 
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            font-size: 8px;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #cbd5e1;
        }
        td { 
            padding: 8px 6px; 
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }
        tr.categoria td {
            background: #f8fafc;
            padding-top: 12px;
            border-bottom: 1px solid #cbd5e1;
        }
        tr.categoria td strong {
            color: #151e26;
            font-size: 10px;
        }
        
        .totals { 
            margin-top: 15px;
            margin-left: auto;
            width: 280px;
        }
        .totals table { margin: 0; }
        .totals td { padding: 6px; border: none; }
        .totals tr:last-child { 
            background: linear-gradient(135deg, #151e26, #151e26);
            color: white;
            font-size: 12px;
            font-weight: 700;
        }
        .totals tr:last-child td { padding: 10px 8px; }
        
        .note {
            margin-top: 20px;
            padding: 12px;
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            border-radius: 0 8px 8px 0;
            font-size: 9px;
            color: #92400e;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            color: #94a3b8;
            font-size: 8px;
        }
        .footer strong { color: #64748b; }
        
        .firma-section {
            margin-top: 40px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #f8fafc;
        }
        .firma-section p {
            font-size: 9px;
            color: #475569;
            margin-bottom: 20px;
            font-style: italic;
            text-align: center;
        }
        .firma-boxes {
            display: flex;
            gap: 40px;
            justify-content: space-between;
        }
        .firma-box {
            flex: 1;
        }
        .firma-label {
            font-size: 8px;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .firma-line {
            border-bottom: 1px solid #94a3b8;
            height: 40px;
            margin-bottom: 5px;
        }
        .firma-data {
            font-size: 8px;
            color: #64748b;
        }
        
        .condizioni {
            margin-top: 20px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 8px;
            font-size: 8px;
            color: #64748b;
        }
        .condizioni h4 {
            color: #475569;
            margin-bottom: 8px;
            font-size: 9px;
        }
        .condizioni ul { margin-left: 12px; }
        .condizioni li { margin: 3px 0; }
        
        .condizioni-extra {
            margin-top: 15px;
            display: flex;
            gap: 15px;
        }
        .condizioni-extra-item {
            flex: 1;
            padding: 12px;
            background: #f8fafc;
            border-radius: 8px;
            font-size: 8px;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }
        .condizioni-extra-item h4 {
            color: #475569;
            margin-bottom: 6px;
            font-size: 9px;
            font-weight: 600;
        }
        .condizioni-extra-item p {
            margin: 0;
            line-height: 1.4;
        }
        
        .burocrazia {
            margin-top: 15px;
            padding: 12px;
            background: #f1f5f9;
            border-radius: 8px;
            font-size: 7px;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }
        .burocrazia h4 {
            color: #475569;
            margin-bottom: 6px;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .burocrazia-content {
            line-height: 1.4;
            text-align: justify;
        }
        .burocrazia-content br {
            margin-bottom: 4px;
            display: block;
            content: "";
        }
        
        @media print {
            body { padding: 40px; }
            .no-print { display: none; }
        }
        
        /* Stile per elenco puntato nella descrizione */
        td p {
            margin: 0;
            line-height: 1.4;
        }
        td ul {
            margin: 0;
            padding-left: 15px;
        }
        td li {
            margin: 2px 0;
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
            {$clienteDettagli}
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
            {$frequenzaTxt}
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
            {$condizioniHtml}
        </ul>
    </div>
    
    <!-- Tempi di consegna e Non include -->
    {$condizioniExtraHtml}
    
    {$burocraziaHtml}
    
    <!-- Sezione Firma -->
    <div class="firma-section">
        <p>Il presente preventivo si intende accettato con firma o timbro del cliente o conferma via email</p>
        <div class="firma-boxes">
            <div class="firma-box">
                <div class="firma-label">Data</div>
                <div class="firma-line"></div>
                <div class="firma-data">__ / __ / ______</div>
            </div>
            <div class="firma-box">
                <div class="firma-label">Firma Cliente</div>
                <div class="firma-line"></div>
                <div class="firma-data">Timbro e/o Firma</div>
            </div>
            <div class="firma-box">
                <div class="firma-label">Firma Fornitore</div>
                <div class="firma-line" style="display:flex;align-items:flex-end;justify-content:center;padding-bottom:5px;">
                    ' . ($firmaHtml ?: '') . '
                </div>
                <div class="firma-data">{$ragioneSociale}</div>
            </div>
        </div>
    </div>
    
    <div class="footer">
        <p><strong>{$ragioneSociale}</strong> | {$indirizzoCompleto} | {$piva} {$cf}</p>
        <p>{$contattiStr}</p>
        <p style="margin-top:10px;font-style:italic;">Grazie per la fiducia accordataci!</p>
    </div>
    
    <div class="no-print" style="margin-top:30px;text-align:center;padding:20px;background:#f8fafc;border-radius:8px;">
        <button onclick="window.print()" style="padding:12px 24px;background:#151e26;color:white;border:none;border-radius:6px;cursor:pointer;font-size:14px;">
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
    $tempiConsegna = $_POST['tempi_consegna'] ?? '';
    $nonInclude = $_POST['non_include'] ?? '';
    $serviziJson = $_POST['servizi'] ?? '[]';
    $subtotale = floatval($_POST['subtotale'] ?? 0);
    $totale = floatval($_POST['totale'] ?? 0);
    $frequenza = intval($_POST['frequenza'] ?? 1);
    $frequenzaTesto = $_POST['frequenza_testo'] ?? 'Una tantum';
    $mostraBurocrazia = filter_var($_POST['mostra_burocrazia'] ?? 'true', FILTER_VALIDATE_BOOLEAN);
    
    if (empty($clienteNome)) {
        jsonResponse(false, null, 'Il nome cliente è obbligatorio');
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Salva nel database
        $stmt = $pdo->prepare("
            INSERT INTO preventivi_salvati 
            (numero, cliente_id, cliente_nome, data_validita, sconto_globale, note, tempi_consegna, non_include, servizi_json, subtotale, totale, frequenza, frequenza_testo, mostra_burocrazia, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $numero,
            $clienteId,
            $clienteNome,
            $dataScadenza ?: null,
            $scontoGlobale,
            $note,
            $tempiConsegna,
            $nonInclude,
            $serviziJson,
            $subtotale,
            $totale,
            $frequenza,
            $frequenzaTesto,
            $mostraBurocrazia ? 1 : 0,
            $_SESSION['user_id']
        ]);
        
        $preventivoId = $pdo->lastInsertId();
        
        // Genera il file HTML del preventivo
        $servizi = json_decode($serviziJson, true);
        $html = generaHTMLPreventivoSalvato($servizi, $clienteNome, $numero, $note, $scontoGlobale, $subtotale, $totale, $dataScadenza, $frequenza, $mostraBurocrazia, $tempiConsegna, $nonInclude);
        
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
 * Genera HTML del preventivo salvato (usa stessa impaginazione del PDF)
 */
function generaHTMLPreventivoSalvato(array $voci, string $cliente, string $numero, string $note, float $scontoGlobale, float $subtotale, float $totale, string $dataScadenza = '', int $frequenza = 1, bool $mostraBurocrazia = true, string $tempiConsegna = '', string $nonInclude = ''): string {
    // Converte il formato dati salvati nel formato usato da generaHTMLPreventivo
    $vociFormattate = [];
    foreach ($voci as $v) {
        $prezzo = floatval($v['prezzo'] ?? 0);
        $qty = intval($v['quantita'] ?? 1);
        $scontoSingolo = floatval($v['sconto_singolo'] ?? 0);
        $scontoListino = intval($v['sconto_percentuale'] ?? 0);
        
        // Calcola il totale riga
        $prezzoScontato = $prezzo * (1 - $scontoSingolo / 100);
        $totaleRiga = $prezzoScontato * $qty;
        
        $vociFormattate[] = [
            'tipo_servizio' => $v['tipo_servizio'] ?? $v['nome'] ?? 'Servizio',
            'descrizione' => $v['descrizione'] ?? '',
            'prezzo' => $prezzo,
            'categoria_nome' => $v['categoria_nome'] ?? 'Servizi',
            'quantita' => $qty,
            'sconto_percentuale' => $scontoListino,
            'sconto_singolo' => $scontoSingolo,
            'totale' => $totaleRiga
        ];
    }
    
    // Usa la stessa funzione di generazione HTML del PDF
    return generaHTMLPreventivo($vociFormattate, $cliente, $numero, $note, $scontoGlobale, $subtotale, $totale, $dataScadenza, $frequenza, $mostraBurocrazia, $tempiConsegna, $nonInclude);
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
