<?php
session_start();
require_once 'config.php';

$msg = '';
$error = '';

if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = trim($_GET['token']);

    // Cerca il cittadino con questo token e non ancora verificato
    $user = $dbInstance->fetchOne("SELECT id FROM cittadini WHERE token_verifica = ? AND verificato = 0", [$token], "s");
    
    if ($user) {
        $cittadino_id = $user['id'];
        
        // Aggiorna lo stato a verificato e rimuovi il token usato
        try {
            $dbInstance->query("UPDATE cittadini SET verificato = 1, token_verifica = NULL WHERE id = ?", [$cittadino_id], "i");
            $msg = "Email verificata con successo! Ora puoi effettuare l'accesso al portale.";
        } catch (Exception $e) {
            $error = "Errore durante l'aggiornamento del profilo nel database.";
        }
    } else {
        $error = "Token non valido, scaduto, oppure l'account è già stato attivato in precedenza.";
    }
} else {
    $error = "Nessun token di verifica fornito nella richiesta HTTP.";
}

$pageTitle = 'Verifica Email - Comune di Palermo';
require_once 'includes/_partials/head.php';
?>

    <main class="main-content">
        <?php 
        $headerIcon = 'fa-envelope-circle-check';
        $headerTitle = 'Verifica Indirizzo Email';
        require_once 'includes/_partials/top_header.php';
        ?>

    <div class="container" style="text-align: center;">
        <?php require_once 'includes/_partials/alerts.php'; ?>
        
        <?php if ($msg): ?>
            <div class="msg info" style="margin-bottom: 20px;">
                <br>
                <strong><?php echo $msg; ?></strong>
            </div>
            <a href="login_cittadino.php" style="display:inline-block; padding:8px 15px; background:#add8e6; color:#000; text-decoration:none; font-weight:bold; border:1px solid #8ab8c6;"><i class="fa-solid fa-right-to-bracket"></i> Vai al Login per Cittadini</a>

        <?php endif; ?>
    </div>
<?php require_once 'includes/_partials/footer.php'; ?>
