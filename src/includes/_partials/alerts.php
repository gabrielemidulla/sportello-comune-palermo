<?php
// Unified alerts for status and error messages
if (isset($msg) && $msg) {
    if (strpos($msg, 'error') !== false || strpos($msg, 'negato') !== false || strpos($msg, 'errata') !== false) {
        echo "<div class='msg error'>$msg</div>";
    } else {
        echo "<div class='msg'>$msg</div>";
    }
}

if (isset($error) && $error) {
    echo "<div class='msg error'>$error</div>";
}

if (isset($errore) && $errore) {
    echo "<div class='msg error'>$errore</div>";
}
?>
