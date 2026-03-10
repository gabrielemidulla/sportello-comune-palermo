<?php
session_start();
require_once 'config.php';

// Controllo d'accesso rigoroso: se la sessione non esiste o è vuota, blocca l'accesso
if (!isset($_SESSION['dipendente_id'])) {
    header('Location: login.php');
    exit;
}

$msg = '';

// Gestione dell'aggiornamento dello stato del ticket (richiesta da form)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['aggiorna_stato'])) {
    $id_segnalazione = (int)$_POST['id_segnalazione'];
    $nuovo_stato = $_POST['stato'];
    
    // Whitelist per prevenire invio di stati malevoli ("In attesa", "Presa in carico", "Risolto")
    $stati_ammessi = ['In attesa', 'Presa in carico', 'Risolto'];
    
    if (in_array($nuovo_stato, $stati_ammessi)) {
        try {
            // Prepared Statement (MySQLi) per UPDATE sicuro
            $stmt = $mysqli->prepare("UPDATE segnalazioni SET stato = ? WHERE id = ?");
            $stmt->bind_param("si", $nuovo_stato, $id_segnalazione);
            $stmt->execute();
            $stmt->close();
            
            // Salvataggio messaggio in sessione per prevenire che un reinvio del form re-invii l'update
            $_SESSION['msg'] = "Stato della pratica aggiornato correttamente.";
            header("Location: dashboard.php");
            exit;
        } catch (mysqli_sql_exception $e) {
            $msg = "<div class='msg error'>Errore durante l'aggiornamento: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        $msg = "<div class='msg error'>Stato non valido inserito.</div>";
    }
}

// Lettura e validazione messaggio di successo proveniente da redirect PRG
if (isset($_SESSION['msg'])) {
    $msg = "<div class='msg'>" . htmlspecialchars($_SESSION['msg']) . "</div>";
    unset($_SESSION['msg']); // ripulisce il messaggio per non mostrarlo ad infinitum
}

// Lettura di tutte le segnalazioni unite ai dati dei cittadini (le nuove pratiche in cima)
$sql_join = "SELECT s.*, c.nome, c.cognome, c.codice_fiscale 
             FROM segnalazioni s 
             JOIN cittadini c ON s.cittadino_id = c.id 
             ORDER BY s.data_inserimento DESC, s.id DESC";
$result = $mysqli->query($sql_join);
$segnalazioni = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $segnalazioni[] = $row;
    }
}

$pageTitle = 'Dashboard Segnalazioni - Comune di Palermo';
require_once 'includes/_partials/head.php';
?>

    <main class="main-content">
        <?php 
        $headerIcon = 'fa-table-list';
        $headerTitle = 'Centro Gestione Segnalazioni';
        require_once 'includes/_partials/top_header.php';
        ?>

        <div class="container" style="max-width: 1200px;">
        <div class="header-with-filter" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3><i class="fa-solid fa-folder-open"></i> Riepilogo Pratiche Cittadini</h3>
            <div class="filter-controls">
                <label for="status-filter"><i class="fa-solid fa-filter"></i> Filtra Stato: </label>
                <select id="status-filter" onchange="changePage(1)" style="padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
                    <option value="">Tutti</option>
                    <option value="In attesa">In attesa</option>
                    <option value="Presa in carico">Presa in carico</option>
                    <option value="Risolto">Risolto</option>
                </select>
            </div>
        </div>
        
        <?php require_once 'includes/_partials/alerts.php'; ?>

        <div class="table-container loading-overlay">
            <table class="bacheca-table">
                <thead>
                    <tr>
                        <th>Ticket ID</th>
                        <th>Data</th>
                        <th>Cittadino</th>
                        <th>Indirizzo</th>
                        <th>Dettagli</th>
                        <th>Stato</th>
                        <th style="width: 150px;">Cambia Stato</th>
                    </tr>
                </thead>
                <tbody id="operator-body">
                    <!-- AJAX results -->
                </tbody>
            </table>
        </div>

        <div id="pagination-container" class="pagination">
            <!-- AJAX buttons -->
        </div>

        <div id="loader" class="spinner-container" style="display:none;">
            <div class="spinner"></div>
            <p>Aggiornamento dati...</p>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        let currentPage = 1;

        function loadOperatorBacheca(page = 1) {
            currentPage = page;
            const statusFilter = $('#status-filter').val();
            
            $('.table-container').addClass('active');
            $('#loader').show();

            $.post('ajax_handler.php', {
                action: 'load_operator_bacheca',
                page: page,
                stato: statusFilter
            }, function(response) {
                $('.table-container').removeClass('active');
                $('#loader').hide();
                
                if (response.success) {
                    $('#operator-body').html(response.html);
                    $('#pagination-container').html(response.pagination);
                } else {
                    alert(response.message || 'Errore durante il caricamento');
                }
            }, 'json');
        }

        function updateStatus(id, newStatus) {
            $('#loader').show();
            $.post('ajax_handler.php', {
                action: 'update_status',
                id: id,
                stato: newStatus
            }, function(response) {
                $('#loader').hide();
                if (response.success) {
                    // Ricarica la pagina corrente per mostrare i badge aggiornati
                    loadOperatorBacheca(currentPage);
                } else {
                    alert(response.message || 'Errore durante l\'aggiornamento');
                }
            }, 'json');
        }

        function changePage(page) {
            loadOperatorBacheca(page);
        }

        // Caricamento iniziale
        $(document).ready(function() {
            loadOperatorBacheca(1);
        });
    </script>
<?php require_once 'includes/_partials/footer.php'; ?>
