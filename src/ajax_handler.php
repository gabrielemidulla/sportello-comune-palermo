<?php
session_start();
require_once 'config.php';

// Solo richieste POST per azioni di modifica/caricamento, tranne per l'autocomplete che può usare GET
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'search_strade')) {
    sendResponse(false, [], 'Metodo non consentito');
}

$action = $_REQUEST['action'] ?? '';

// 1. GESTIONE UPVOTE / CANCEL VOTE
if ($action === 'toggle_upvote') {
    if (!isset($_SESSION['cittadino_id'])) {
        sendResponse(false, [], 'Devi essere loggato per votare');
    }

    $cittadino_id = (int)$_SESSION['cittadino_id'];
    $segnalazione_id = (int)($_POST['segnalazione_id'] ?? 0);

    // Verifica lo stato della segnalazione prima di permettere il voto
    $res_check = $dbInstance->fetchOne("SELECT stato FROM segnalazioni WHERE id = ?", [$segnalazione_id], "i");

    if ($res_check && $res_check['stato'] === 'Risolto') {
        sendResponse(false, [], 'Non è possibile votare una pratica già risolta.');
    }

    // Verifica se il voto esiste già
    $voto_esiste = $dbInstance->fetchOne("SELECT 1 FROM upvotes_segnalazioni WHERE segnalazione_id = ? AND cittadino_id = ?", [$segnalazione_id, $cittadino_id], "ii");

    if ($voto_esiste) {
        $dbInstance->query("DELETE FROM upvotes_segnalazioni WHERE segnalazione_id = ? AND cittadino_id = ?", [$segnalazione_id, $cittadino_id], "ii");
        $status = 'removed';
    } else {
        $dbInstance->query("INSERT INTO upvotes_segnalazioni (segnalazione_id, cittadino_id) VALUES (?, ?)", [$segnalazione_id, $cittadino_id], "ii");
        $status = 'added';
    }

    // Conta nuovi voti totali
    $res_count = $dbInstance->fetchOne("SELECT COUNT(*) as count FROM upvotes_segnalazioni WHERE segnalazione_id = ?", [$segnalazione_id], "i");

    sendResponse(true, [
        'status' => $status, 
        'new_count' => $res_count['count']
    ]);
}

// 2. CARICAMENTO PAGINA BACHECA (AJAX Paginazione e Sorting)
if ($action === 'load_bacheca') {
    $limite_per_pagina = 5;
    $pagina = (int)($_POST['page'] ?? 1);
    $sort = $_POST['sort'] ?? 'upvotes';
    $order = $_POST['order'] ?? 'DESC';
    
    if ($pagina < 1) $pagina = 1;
    $offset = ($pagina - 1) * $limite_per_pagina;
    $filter_strada = !empty($_POST['strada_id']) ? (int)$_POST['strada_id'] : null;

    // Mapping dei campi per il sorting sicuro
    $sort_map = [
        'data' => 's.data_inserimento',
        'upvotes' => 'upvotes',
        'categoria' => 's.categoria',
        'indirizzo' => 'indirizzo'
    ];
    $order_by = $sort_map[$sort] ?? 's.data_inserimento';
    $order_direction = ($order === 'ASC') ? 'ASC' : 'DESC';

    $corrente_cittadino = isset($_SESSION['cittadino_id']) ? (int)$_SESSION['cittadino_id'] : 0;

    // Filtro per strada se presente
    $where_clause = "";
    if ($filter_strada) {
        $where_clause = " WHERE s.strada_id = " . (int)$filter_strada;
    }

    // Conteggio totale con eventuale filtro
    $totale_row = $dbInstance->fetchOne("SELECT COUNT(*) AS tot FROM segnalazioni s" . $where_clause);
    $totale_segnalazioni = $totale_row['tot'];
    $totale_pagine = ceil($totale_segnalazioni / $limite_per_pagina);

    // Query con JOIN per recuperare l'indirizzo dalla tabella strade o da backup
    $query = "
        SELECT 
            s.id, s.codice_pratica, s.categoria, s.descrizione, s.data_inserimento, s.stato,
            s.cittadino_id AS creatore_id,
            st.indirizzo_completo AS indirizzo,
            (SELECT COUNT(*) FROM upvotes_segnalazioni u WHERE u.segnalazione_id = s.id) AS upvotes,
            (SELECT COUNT(*) FROM upvotes_segnalazioni u2 WHERE u2.segnalazione_id = s.id AND u2.cittadino_id = ?) AS user_has_upvoted
        FROM segnalazioni s
        LEFT JOIN strade st ON s.strada_id = st.id
        $where_clause
        ORDER BY $order_by $order_direction, s.data_inserimento DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $dbInstance->query($query, [$corrente_cittadino, $limite_per_pagina, $offset], "iii");
    $res = $stmt->get_result();

    $html = '';
    while($row = $res->fetch_assoc()) {
        $html .= renderSegnalazioneRow($row, $corrente_cittadino);
    }

    if ($res->num_rows == 0) {
        $html = '<tr><td colspan="5" style="text-align: center; padding: 20px;"><i class="fa-solid fa-leaf"></i> La bacheca è pulita.</td></tr>';
    }

    // Generazione Paginazione
    $pagination_html = '';
    if ($totale_pagine > 1) {
        if ($pagina > 1) {
            $pagination_html .= '<button onclick="changePage('.($pagina-1).')"><i class="fa-solid fa-chevron-left"></i> Precedente</button>';
        }
        for ($i = 1; $i <= $totale_pagine; $i++) {
            $active = ($i == $pagina) ? 'class="current"' : '';
            $pagination_html .= '<button '.$active.' onclick="changePage('.$i.')">'.$i.'</button>';
        }
        if ($pagina < $totale_pagine) {
            $pagination_html .= '<button onclick="changePage('.($pagina+1).')">Successivo <i class="fa-solid fa-chevron-right"></i></button>';
        }
    }

    sendResponse(true, [
        'html' => $html,
        'pagination' => $pagination_html,
        'total_pages' => $totale_pagine,
        'current_page' => $pagina
    ]);
}

// 2.5 GESTIONE OPERATORE (Solo Dipendenti)
if ($action === 'load_operator_bacheca' || $action === 'update_status') {
    if (!isset($_SESSION['dipendente_id'])) {
        sendResponse(false, [], 'Accesso negato');
    }
}

if ($action === 'load_operator_bacheca') {
    $pagina = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $stato_filter = $_POST['stato'] ?? '';
    $limite = 8;
    $offset = ($pagina - 1) * $limite;

    $where = "1=1";
    $params = [];
    $types = "";

    if (!empty($stato_filter)) {
        $where .= " AND s.stato = ?";
        $params[] = $stato_filter;
        $types .= "s";
    }

    // Conteggio totale
    $count_sql = "SELECT COUNT(*) as tot FROM segnalazioni s WHERE $where";
    $totale = $dbInstance->fetchOne($count_sql, $params, $types)['tot'];
    $totale_pagine = ceil($totale / $limite);

    // Query dati
    $query = "
        SELECT s.*, c.nome, c.cognome, c.codice_fiscale, st.indirizzo_completo as via_ufficiale
        FROM segnalazioni s 
        JOIN cittadini c ON s.cittadino_id = c.id 
        LEFT JOIN strade st ON s.strada_id = st.id
        WHERE $where 
        ORDER BY s.data_inserimento DESC 
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limite;
    $params[] = $offset;
    $types .= "ii";

    $stmt = $dbInstance->query($query, $params, $types);
    $res = $stmt->get_result();

    $html = '';
    while($row = $res->fetch_assoc()) {
        $badge_class = 'bg-attesa';
        if ($row['stato'] === 'Presa in carico') $badge_class = 'bg-carico';
        elseif ($row['stato'] === 'Risolto') $badge_class = 'bg-risolto';

        $via = $row['via_ufficiale'] ?: $row['indirizzo'];

        $html .= '<tr>';
        $html .= '<td>#'.$row['id'].'</td>';
        $html .= '<td>'.date('d/m/Y H:i', strtotime($row['data_inserimento'])).'</td>';
        $html .= '<td><strong>'.htmlspecialchars($row['nome'].' '.$row['cognome']).'</strong><br><small>CF: '.htmlspecialchars($row['codice_fiscale']).'</small></td>';
        $html .= '<td>'.htmlspecialchars($via).'</td>';
        $html .= '<td><strong>['.htmlspecialchars($row['categoria']).']</strong><br>'.htmlspecialchars($row['descrizione']).'</td>';
        $html .= '<td><span class="badge '.$badge_class.'">'.htmlspecialchars($row['stato']).'</span></td>';
        $html .= '<td>';
        $html .= '<select onchange="updateStatus('.$row['id'].', this.value)" style="padding: 4px; font-size: 11px;">';
        $html .= '<option value="In attesa" '.($row['stato']=='In attesa'?'selected':'').'>In attesa</option>';
        $html .= '<option value="Presa in carico" '.($row['stato']=='Presa in carico'?'selected':'').'>Presa in carico</option>';
        $html .= '<option value="Risolto" '.($row['stato']=='Risolto'?'selected':'').'>Risolto</option>';
        $html .= '</select>';
        $html .= '</td>';
        $html .= '</tr>';
    }

    if ($totale == 0) {
        $html = '<tr><td colspan="7" style="text-align: center; padding: 20px;">Nessuna segnalazione trovata.</td></tr>';
    }

    $pagination_html = '';
    if ($totale_pagine > 1) {
        for ($i = 1; $i <= $totale_pagine; $i++) {
            $active = ($i == $pagina) ? 'class="current"' : '';
            $pagination_html .= '<button '.$active.' onclick="changePage('.$i.')">'.$i.'</button>';
        }
    }

    sendResponse(true, [
        'html' => $html,
        'pagination' => $pagination_html,
        'total' => $totale
    ]);
}

if ($action === 'update_status') {
    $id = (int)($_POST['id'] ?? 0);
    $stato = $_POST['stato'] ?? '';
    $stati_ammessi = ['In attesa', 'Presa in carico', 'Risolto'];

    if (!in_array($stato, $stati_ammessi)) {
        sendResponse(false, [], 'Stato non valido');
    }

    $dbInstance->query("UPDATE segnalazioni SET stato = ? WHERE id = ?", [$stato, $id], "si");
    sendResponse(true, [], 'Stato aggiornato');
}

// 3. AUTOCOMPLETE STRADE (SQL Locale)
if ($action === 'search_strade') {
    $term = $_REQUEST['term'] ?? ($_REQUEST['q'] ?? '');
    if (strlen($term) < 2) {
        echo json_encode([]);
        exit;
    }

    $term_like = "%" . $term . "%";
    $results_data = $dbInstance->fetchAll("
        SELECT id, indirizzo_completo, cap 
        FROM strade 
        WHERE indirizzo_completo LIKE ? 
        LIMIT 10
    ", [$term_like], "s");

    $results = [];
    foreach ($results_data as $row) {
        $results[] = [
            'id' => $row['id'],
            'label' => $row['indirizzo_completo'] . " (" . $row['cap'] . ")",
            'value' => $row['indirizzo_completo']
        ];
    }
    
    echo json_encode($results);
    exit;
}
?>
