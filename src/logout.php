<?php
session_start();

// Distruggiamo prima tutte le variabili di sessione dell'array superglobale
$_SESSION = array();

// Se è necessario distruggere completamente la sessione, eliminiamo anche il cookie di sessione.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Distrugge fisicamente il file di sessione dal server
session_destroy();

// Redirect alla pagina di partenza pubblica
header('Location: index.php');
exit;
?>
