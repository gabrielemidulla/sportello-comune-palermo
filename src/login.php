<?php
session_start();
require_once 'config.php';

// Se l'utente è già loggato, bypassa il login e reindirizza alla dashboard
if (isset($_SESSION['dipendente_id'])) {
    header('Location: dashboard.php');
    exit;
}

$errore = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $matricola = trim($_POST['matricola']);
    $password = $_POST['password'];

    $user = $dbInstance->fetchOne("SELECT id, password_hash FROM dipendenti WHERE matricola = ?", [$matricola], "s");

    if ($user) {
        if (password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);

            $_SESSION['dipendente_id'] = $user['id'];
            $_SESSION['matricola'] = $matricola;
            
            header('Location: dashboard.php');
            exit;
        } else {
            $errore = "Password errata.";
        }
    } else {
        $errore = "Matricola non trovata.";
    }
}

$pageTitle = 'Login Area Riservata - Comune di Palermo';
require_once 'includes/_partials/head.php';
?>

    <main class="main-content">
        <?php 
        $headerIcon = 'fa-user-shield';
        $headerTitle = 'Area Riservata Dipendenti';
        require_once 'includes/_partials/top_header.php';
        ?>

    <div class="container" style="max-width: 400px;">
        <h3><i class="fa-solid fa-door-open"></i> Accesso al Sistema</h3>
        
        <?php require_once 'includes/_partials/alerts.php'; ?>

        <form action="login.php" method="POST">
            <label for="matricola"><i class="fa-solid fa-id-badge"></i> Matricola Operatore (es. admin)</label>
            <input type="text" id="matricola" name="matricola" required>

            <label for="password"><i class="fa-solid fa-lock"></i> Password</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">
                Accedi
            </button>
        </form>
    </div>
<?php require_once 'includes/_partials/footer.php'; ?>
