<?php
session_start();
require_once 'config.php';

$msg = '';
$stato_ricerca = null;



// Gestione Upvote (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upvote_pratica'])) {
    if (!isset($_SESSION['cittadino_id'])) {
        $msg = "<div class='msg error'><i class='fa-solid fa-triangle-exclamation'></i> Devi essere loggato per supportare una segnalazione.</div>";
    } else {
        $cittadino_id = (int)$_SESSION['cittadino_id'];
        $segnalazione_id = (int)$_POST['segnalazione_id'];
        
        try {
            $dbInstance->query("INSERT IGNORE INTO upvotes_segnalazioni (segnalazione_id, cittadino_id) VALUES (?, ?)", [$segnalazione_id, $cittadino_id], "ii");
            $msg = "<i class='fa-solid fa-circle-check'></i> Hai aggiunto il tuo supporto alla segnalazione!";
        } catch (Exception $e) { 
            // Ignora errori di duplicato o altro in questo contesto specifico se si desidera mantenere il comportamento originale INSERT IGNORE
        }
    }
}

// Paginazione
$limite_per_pagina = 5;
$pagina_corrente = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($pagina_corrente < 1) $pagina_corrente = 1;
$offset = ($pagina_corrente - 1) * $limite_per_pagina;

// Conteggio totale per calcolare il numero di pagine
$totale_row = $dbInstance->fetchOne("SELECT COUNT(*) AS tot FROM segnalazioni");
$totale_segnalazioni = $totale_row['tot'];
$totale_pagine = ceil($totale_segnalazioni / $limite_per_pagina);

// Fetch bacheca pubblica con limit/offset
$corrente_cittadino = isset($_SESSION['cittadino_id']) ? (int)$_SESSION['cittadino_id'] : 0;
$query_segnalazioni = "
    SELECT 
        s.id, s.codice_pratica, st.indirizzo_completo AS indirizzo, s.categoria, s.descrizione, s.data_inserimento, s.stato,
        s.cittadino_id AS creatore_id,
        c.nome, c.cognome,
        (SELECT COUNT(*) FROM upvotes_segnalazioni u WHERE u.segnalazione_id = s.id) AS upvotes,
        (SELECT COUNT(*) FROM upvotes_segnalazioni u2 WHERE u2.segnalazione_id = s.id AND u2.cittadino_id = ?) AS user_has_upvoted
    FROM segnalazioni s
    LEFT JOIN cittadini c ON s.cittadino_id = c.id
    LEFT JOIN strade st ON s.strada_id = st.id
    ORDER BY upvotes DESC, s.data_inserimento DESC
    LIMIT ? OFFSET ?
";
$stmt_seg = $dbInstance->query($query_segnalazioni, [$corrente_cittadino, $limite_per_pagina, $offset], "iii");
$risultato_segnalazioni = $stmt_seg->get_result();

$pageTitle = 'Sportello del Cittadino - Comune di Palermo';
require_once 'includes/_partials/head.php';
?>

    <main class="main-content">
        <?php 
        $headerIcon = 'fa-users';
        $headerTitle = 'Bacheca Pubblica Segnalazioni';
        require_once 'includes/_partials/top_header.php';
        ?>

        <div class="container">
        <!-- Mostra eventuale messaggio di successo dopo l'inserimento -->
        <?php require_once 'includes/_partials/alerts.php'; ?>
        
        <div class="dashboard-controls" style="margin-bottom: 20px;">
            <p style="font-size: 13px; color: #555;"><i class="fa-solid fa-info-circle"></i> Tutte le richieste inviate dai cittadini. Clicca sulle intestazioni della tabella per ordinare i risultati.</p>
        </div>
        
        <table class="bacheca-table">
            <thead>
                <tr>
                    <th style="width: 100px; text-align: center; cursor:pointer;" onclick="changeSort('upvotes')">
                        <i class="fa-solid fa-angle-up"></i> Voti <span id="sort-icon-upvotes"></span>
                    </th>
                    <th>Dettagli Pratica</th>
                    <th style="cursor:pointer; width: 300px;">
                        <div class="header-with-combobox">
                            <span onclick="changeSort('indirizzo')" style="white-space: nowrap;">
                                <i class="fa-solid fa-map-marker-alt"></i> Indirizzo <span id="sort-icon-indirizzo"></span>
                            </span>
                            
                            <!-- Address Combobox for Filtering -->
                            <div class="custom-combobox dashboard-filter">
                                <button type="button" class="combobox-toggle" id="filter-address-btn">
                                    <i class="fa-solid fa-filter"></i> <span id="filter-address-text">Tutte</span>
                                </button>
                                <div class="combobox-dropdown" id="filter-dropdown">
                                    <div class="combobox-search-container">
                                        <input type="text" id="filter-search" placeholder="Cerca via..." autocomplete="off">
                                    </div>
                                    <div class="combobox-results" id="filter-results">
                                        <!-- AJAX results -->
                                    </div>
                                </div>
                                <input type="hidden" id="filter-strada-id">
                                <button type="button" id="clear-filter-btn" class="btn-clear" style="display:none;" title="Pulisci filtro">
                                    <i class="fa-solid fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </th>
                    <th style="cursor:pointer;" onclick="changeSort('data')">
                        <i class="fa-solid fa-calendar-alt"></i> Data <span id="sort-icon-data"></span>
                    </th>
                    <th style="width: 120px; text-align: center;">Stato</th>
                </tr>
            </thead>
            <tbody id="bacheca-body" class="loading-overlay">
                <?php while($row = $risultato_segnalazioni->fetch_assoc()): 
                    echo renderSegnalazioneRow($row, $corrente_cittadino);
                endwhile; ?>
                <?php if($risultato_segnalazioni->num_rows == 0): ?>
                    <tr><td colspan="5" style="text-align: center; padding: 20px;"><i class="fa-solid fa-leaf"></i> La bacheca è pulita. Nessuna segnalazione al momento.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div id="pagination-container" class="pagination">
            <?php if ($totale_pagine > 1): ?>
                <?php if ($pagina_corrente > 1): ?>
                    <button onclick="changePage(<?php echo $pagina_corrente - 1; ?>)"><i class="fa-solid fa-chevron-left"></i> Precedente</button>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totale_pagine; $i++): ?>
                    <button class="<?php echo ($i == $pagina_corrente) ? 'current' : ''; ?>" onclick="changePage(<?php echo $i; ?>)"><?php echo $i; ?></button>
                <?php endfor; ?>

                <?php if ($pagina_corrente < $totale_pagine): ?>
                    <button onclick="changePage(<?php echo $pagina_corrente + 1; ?>)">Successivo <i class="fa-solid fa-chevron-right"></i></button>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div id="loader" class="spinner-container">
            <div class="spinner"></div>
            <p>Caricamento in corso...</p>
        </div>

        <script>
        function toggleVote(id) {
            const $btn = $('#btn-' + id);
            const $countSpan = $('#count-' + id);
            
            $.post('ajax_handler.php', {
                action: 'toggle_upvote',
                segnalazione_id: id
            }, function(data) {
                if (data.success) {
                    $countSpan.text(data.new_count);
                    if (data.status === 'added') {
                        $btn.addClass('voted').css({backgroundColor: '#4caf50', color: '#fff'})
                            .html('<i class="fa-solid fa-check"></i> Rimuovi');
                    } else {
                        $btn.removeClass('voted').css({backgroundColor: '', color: ''})
                            .html('<i class="fa-solid fa-arrow-up"></i> Vota');
                    }
                } else {
                    alert(data.message);
                }
            }, 'json').fail(function() {
                console.error('Errore durante il voto');
            });
        }

        let currentPage = <?php echo $pagina_corrente; ?>;
        let currentSort = 'upvotes';
        let currentOrder = 'DESC';
        let currentStradaId = null;

        // Custom Combobox for Filtering
        const $filterBtn = $('#filter-address-btn');
        const $filterDropdown = $('#filter-dropdown');
        const $filterSearch = $('#filter-search');
        const $filterResults = $('#filter-results');
        const $filterText = $('#filter-address-text');
        const $hiddenStradaId = $('#filter-strada-id');
        const $clearBtn = $('#clear-filter-btn');

        $filterBtn.on('click', function(e) {
            e.stopPropagation();
            $filterDropdown.toggle();
            if ($filterDropdown.is(':visible')) {
                $filterSearch.focus();
            }
        });

        // Close dropdown when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.custom-combobox').length) {
                $filterDropdown.hide();
            }
        });

        $filterSearch.on('input', function() {
            const term = $(this).val();
            if (term.length < 2) {
                $filterResults.empty();
                return;
            }

            $.getJSON('ajax_handler.php', {
                action: 'search_strade',
                term: term
            }, function(data) {
                $filterResults.empty();
                if (data.length === 0) {
                    $filterResults.append('<div class="combobox-no-results">Nessuna strada trovata</div>');
                } else {
                    data.forEach(item => {
                        const $item = $('<div class="combobox-item"></div>').text(item.label);
                        $item.on('click', function() {
                            $filterText.text(item.value);
                            currentStradaId = item.id;
                            $hiddenStradaId.val(item.id);
                            $clearBtn.show();
                            $filterDropdown.hide();
                            loadBacheca(1);
                        });
                        $filterResults.append($item);
                    });
                }
            });
        });

        $clearBtn.on('click', function(e) {
            e.stopPropagation();
            currentStradaId = null;
            $hiddenStradaId.val('');
            $filterText.text('Tutte');
            $filterSearch.val('');
            $filterResults.empty();
            $clearBtn.hide();
            loadBacheca(1);
        });

        function changeSort(field) {
            if (currentSort === field) {
                currentOrder = (currentOrder === 'DESC') ? 'ASC' : 'DESC';
            } else {
                currentSort = field;
                currentOrder = 'DESC'; // Default al più recente/più votato
            }
            updateSortIcons();
            loadBacheca(1);
        }

        function updateSortIcons() {
            // Reset icons
            $('#sort-icon-upvotes, #sort-icon-indirizzo, #sort-icon-data').html('');
            
            const icon = (currentOrder === 'DESC') ? ' <i class="fa-solid fa-caret-down"></i>' : ' <i class="fa-solid fa-caret-up"></i>';
            $('#sort-icon-' + currentSort).html(icon);
        }

        function changePage(page) {
            loadBacheca(page);
        }

        function loadBacheca(page) {
            const $body = $('#bacheca-body');
            const $loader = $('#loader');
            const $pagination = $('#pagination-container');
            
            $body.addClass('active');
            $loader.show();
            
            $.post('ajax_handler.php', {
                action: 'load_bacheca',
                page: page,
                sort: currentSort,
                order: currentOrder,
                strada_id: currentStradaId
            }, function(data) {
                if (data.success) {
                    $body.html(data.html);
                    $pagination.html(data.pagination);
                    currentPage = page;
                    window.history.pushState({page: page, sort: currentSort, order: currentOrder, strada_id: currentStradaId}, "", "?page=" + page + "&sort=" + currentSort + "&order=" + currentOrder + (currentStradaId ? "&strada_id=" + currentStradaId : ""));
                }
            }, 'json').fail(function() {
                console.error('Errore caricamento');
            }).always(function() {
                $body.removeClass('active');
                $loader.hide();
            });
        }

        // Inizializzazione icone al caricamento
        $(document).ready(function() {
            updateSortIcons();
        });
        </script>

        </div>
<?php require_once 'includes/_partials/footer.php'; ?>
