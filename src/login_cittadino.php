<?php
session_start();
require_once 'config.php';

// Se sei loggato bypassa
if (isset($_SESSION['cittadino_id'])) {
    header('Location: index.php');
    exit;
}
if (isset($_SESSION['dipendente_id'])) {
    header('Location: dashboard.php');
    exit;
}

$errore = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Recupera i dati del cittadino dal DB usando i Prepared Statement
    $stmt = $mysqli->prepare("SELECT id, nome, cognome, password_hash, verificato FROM cittadini WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user) {
        // Verifica la password e lo stato di attivazione dell'account
        if (password_verify($password, $user['password_hash'])) {
            if ($user['verificato'] == 1) {
                // Autenticazione ok: Rigenera SessID
                session_regenerate_id(true);
                
                // Salva le credenziali in sessione, separandole da quelle dei dipendenti
                $_SESSION['cittadino_id'] = $user['id'];
                $_SESSION['nome_cittadino'] = $user['nome'];
                $_SESSION['cognome_cittadino'] = $user['cognome'];
                
                // Reindirizza allo sportello pubblico
                header('Location: index.php');
                exit;
            } else {
                $errore = "Accesso negato: devi prima verificare la tua email cliccando sul link che ti è stato inviato in fase di registrazione.";
            }
        } else {
            $errore = "Password errata.";
        }
    } else {
        $errore = "Indirizzo email non trovato.";
    }
}

$pageTitle = 'Login Cittadino - Comune di Palermo';
require_once 'includes/_partials/head.php';
?>

    <main class="main-content">
        <?php 
        $headerIcon = 'fa-users';
        $headerTitle = 'Accesso al Portale del Cittadino';
        require_once 'includes/_partials/top_header.php';
        ?>

    <div class="container" style="max-width: 400px;">
        <h3><i class="fa-solid fa-right-to-bracket"></i> Login Cittadini</h3>
        
        <?php require_once 'includes/_partials/alerts.php'; ?>

        <form action="login_cittadino.php" method="POST">
            <label for="email"><i class="fa-solid fa-envelope"></i> Indirizzo Email</label>
            <input type="email" id="email" name="email" required>

            <label for="password"><i class="fa-solid fa-lock"></i> Password</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">
                Accedi alle Segnalazioni
            </button>
        </form>
    </div>
<?php require_once 'includes/_partials/footer.php'; ?>
