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

    // Prepared Statement (MySQLi) per cercare la matricola nel database in modo sicuro (previene SQL Injection)
    $user = $dbInstance->fetchOne("SELECT id, password_hash FROM dipendenti WHERE matricola = ?", [$matricola], "s");

    if ($user) {
        // --- COMMENTO DIDATTICO SULLA SICUREZZA DELLE PASSWORD E PASSWORD_HASH ---
        // Nelle lezioni precedenti il professore potrebbe aver citato algoritmi di hashing tradizionali 
        // e più vecchiotti come `md5()`, `sha1()` o `crypt()`.
        // Tuttavia, è cruciale comprendere che MD5 (e simili) NON deve MAI essere usato per memorizzare password.
        // Il problema di MD5 è che è "troppo veloce": questo permette ad hacker malintenzionati di eseguire 
        // attacchi "Brute-Force" provando milioni di password al secondo, oppure usare database immensi 
        // pre-calcolati noti come "Rainbow Tables" per scovare password semplici quasi istantaneamente.
        //
        // Per questo motivo, il codice utilizza `password_hash()` e `password_verify()` di PHP.
        // Di default, PHP utilizza l'algoritmo BCRYPT asimmetrico. BCRYPT non solo genera automaticamente
        // un "salt" (una stringa casuale mescolata alla password) per sconfiggere le Rainbow Tables
        // (due password uguali avranno hash diversi nel database!), ma include anche un "Cost Factor".
        // Questo costo rallenta volontariamente il processo di decrittazione (es. facendolo durare 100ms
        // invece di 0.001ms), rendendo gli attacchi brute-force computazionalmente inattuabili o 
        // ridicolmente lenti per un attaccante. 
        // `password_verify()` poi, confronta la stringa immessa con l'hash e il salt salvati nel DB, 
        // proteggendo nativamente anche da "Timing Attacks".
        // -----------------------------------------------------------------------------------------
        
        if (password_verify($password, $user['password_hash'])) {
            // Autenticazione riuscita, rigenera l'id sessione per prevenire attacchi di tipo Session Fixation
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
