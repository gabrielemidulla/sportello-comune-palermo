<?php
session_start();
require_once 'config.php';

$msg = '';

if (!isset($_SESSION['cittadino_id'])) {
    header('Location: login_cittadino.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['inserisci'])) {
    
    $cittadino_id = (int)$_SESSION['cittadino_id'];
    $strada_id = !empty($_POST['strada_id']) ? (int)$_POST['strada_id'] : null;
    $indirizzo_manuale = trim($_POST['indirizzo'] ?? '');
    $categoria = $_POST['categoria'];
    $descrizione = trim($_POST['descrizione']);
    
    $stringa_da_hashare = $cittadino_id . $descrizione . time();
    $codice_pratica = hash('sha256', $stringa_da_hashare);

    try {
        $sql = "INSERT INTO segnalazioni (codice_pratica, cittadino_id, strada_id, indirizzo, categoria, descrizione) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $dbInstance->query($sql, [$codice_pratica, $cittadino_id, $strada_id, $indirizzo_manuale, $categoria, $descrizione], "siisss");
        
        $msg = "<div class='msg'><i class='fa-solid fa-circle-check'></i> Segnalazione inviata con successo!<br><br>Il tuo <strong>Codice Pratica</strong> è: <br><code>" . htmlspecialchars($codice_pratica) . "</code><br><br>Conservalo per riferimenti futuri. <a href='index.php'>Torna alla bacheca</a></div>";
    } catch (Exception $e) {
        $msg = "<div class='msg error'>Errore durante l'inserimento: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

$pageTitle = 'Nuova Segnalazione - Comune di Palermo';
require_once 'includes/_partials/head.php';
?>

    <main class="main-content">
        <?php 
        $headerIcon = 'fa-pen-to-square';
        $headerTitle = 'Apri una Nuova Segnalazione';
        require_once 'includes/_partials/top_header.php';
        ?>

        <div class="container container-form">
            <?php if ($msg): ?>
                <?php require_once 'includes/_partials/alerts.php'; ?>
            <?php else: ?>
                <p>Compila il modulo per inviare una richiesta al Comune di Palermo.</p>
                <form action="nuova_segnalazione.php" method="POST">
                    
                    <div style="background-color: #f4f4f4; padding: 10px; border: 1px solid #ddd; margin-bottom: 15px; font-size:13px;">
                        <strong><i class="fa-solid fa-id-card"></i> Dati Richiedente:</strong><br>
                        <?php echo htmlspecialchars($_SESSION['nome_cittadino'] . ' ' . $_SESSION['cognome_cittadino']); ?> 
                        (I dati amministrativi sono collegati in automatico ed accessibili solo allo Sportello)
                    </div>

                    <label for="address-selector">Indirizzo del disservizio</label>
                    <div class="custom-combobox">
                        <button type="button" id="combobox-trigger" class="combobox-toggle">
                            <span id="selected-address-text">Seleziona un indirizzo...</span>
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <div id="combobox-dropdown" class="combobox-dropdown">
                            <div class="combobox-search-container">
                                <input type="text" id="combobox-search" placeholder="Cerca via o piazza (es. Roma)..." autocomplete="off">
                            </div>
                            <div id="combobox-results" class="combobox-results">
                                <!-- Risultati AJAX -->
                            </div>
                        </div>
                        <input type="hidden" id="indirizzo" name="indirizzo" required>
                        <input type="hidden" id="strada_id" name="strada_id">
                    </div>

                    <label for="categoria">Categoria del Disservizio</label>
                    <select id="categoria" name="categoria" required>
                        <option value="Buca stradale">Buca stradale</option>
                        <option value="Illuminazione">Illuminazione Pubblica</option>
                        <option value="Rifiuti">Rifiuti e Decoro Urbano</option>
                        <option value="Altro">Altro</option>
                    </select>

                    <label for="descrizione">Descrizione dettagliata del problema (max 500 car.)</label>
                    <div class="textarea-container">
                        <textarea id="descrizione" name="descrizione" rows="5" maxlength="500" required></textarea>
                        <div id="char-counter" class="char-counter">0 / 500</div>
                    </div>

                    <button type="submit" name="inserisci" id="btn-submit" disabled>
                        <i class="fa-regular fa-paper-plane"></i> Invia Segnalazione
                    </button>
                    
                    <a href="index.php" style="display:inline-block; margin-top:15px; padding:10px 15px; background:#f4f4f4; color:#333; text-decoration:none; border:1px solid #ccc; font-weight:bold; text-align:center;"><i class="fa-solid fa-arrow-left"></i> Annulla</a>
                </form>
            <?php endif; ?>
        </div>

    <script src="js/nuova_segnalazione.js"></script>
<?php require_once 'includes/_partials/footer.php'; ?>
