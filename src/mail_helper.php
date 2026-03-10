<?php
/**
 * Semplice funzione per l'invio di email tramite SMTP diretto (es. Mailpit/MailHog).
 * Evita l'uso di PHPMailer o modifiche al container (come l'installazione di sendmail).
 */
function inviaEmailSetup($to, $subject, $message) {
    $smtp_host = 'mailpit'; // Nome del container SMTP in docker-compose
    $smtp_port = 1025;      // Porta standard per ricezione SMTP senza TLS

    // Intestazioni minime per una mail HTML
    $headers = "From: no-reply@comune.palermo.it\r\n";
    $headers .= "Reply-To: no-reply@comune.palermo.it\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    // Formattazione corpo mail
    $body = $headers . "\r\n" . $message . "\r\n.\r\n";

    // Apertura socket verso il server SMTP locale
    $socket = @fsockopen($smtp_host, $smtp_port, $errno, $errstr, 5);
    if (!$socket) {
        error_log("Timeout o errore connessione SMTP: $errstr ($errno)");
        return false;
    }

    // Lettura banner iniziale
    fgets($socket, 515);

    // Dialogo SMTP basilare
    fwrite($socket, "HELO localhost\r\n");
    fgets($socket, 515);

    fwrite($socket, "MAIL FROM: <no-reply@comune.palermo.it>\r\n");
    fgets($socket, 515);

    fwrite($socket, "RCPT TO: <$to>\r\n");
    fgets($socket, 515);

    fwrite($socket, "DATA\r\n");
    fgets($socket, 515);

    // Inseriamo anche Subject e To direttamente nel payload DATA per garantirne la lettura
    $full_message = "Subject: $subject\r\nTo: $to\r\n" . $body;
    fwrite($socket, $full_message);
    $response = fgets($socket, 515);

    fwrite($socket, "QUIT\r\n");
    fgets($socket, 515);
    fclose($socket);

    // Se l'SMTP ha risposto con 250 OK dopo il DATA, la mail è stata accettata
    return strpos($response, '250') !== false;
}
?>
