<?php
session_start();
require_once 'config.php';
require_once 'mail_helper.php';

// Se sei già loggato come cittadino o come operatore, impedisci la registrazione
if (isset($_SESSION['cittadino_id']) || isset($_SESSION['dipendente_id'])) {
    header('Location: index.php');
    exit;
}

$msg = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['registra'])) {
    $nome = trim($_POST['nome']);
    $cognome = trim($_POST['cognome']);
    $codice_fiscale = strtoupper(trim($_POST['codice_fiscale']));
    $data_nascita = $_POST['data_nascita'];
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $conferma_password = $_POST['conferma_password'];

    // Validazioni LATO SERVER
    if ($password !== $conferma_password) {
        $error = "Le password non coincidono.";
    } elseif (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || preg_match_all('/[^a-zA-Z0-9]/', $password) < 1) {
        $error = "La password non rispetta i requisiti di sicurezza minimi (8 caratteri, 1 maiuscola, 1 speciale).";
    } else {
        // Controllo se email o CF esistono già
        $result_check = $dbInstance->fetchOne("SELECT id FROM cittadini WHERE email = ? OR codice_fiscale = ?", [$email, $codice_fiscale], "ss");

        if ($result_check) {
            $error = "Errore: Email o Codice Fiscale già registrati a sistema.";
        } else {
            // Hashing e generazione Token (criptograficamente sicuro)
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $token_verifica = bin2hex(random_bytes(32));

            // Inserimento a DB
            try {
                $dbInstance->query("INSERT INTO cittadini (nome, cognome, codice_fiscale, data_nascita, email, password_hash, token_verifica) VALUES (?, ?, ?, ?, ?, ?, ?)", 
                    [$nome, $cognome, $codice_fiscale, $data_nascita, $email, $password_hash, $token_verifica], "sssssss");
                
                // Invio email con link di verifica HTTP (Mailpit girerà in locale alla porta 8025)
                $host = $_SERVER['HTTP_HOST'];
                $verify_link = "http://" . $host . "/verifica.php?token=" . $token_verifica;
                
                $subject = "Comune di Palermo - Verifica la tua email";
                $html_message = "
                    <h3>Benvenuto nello Sportello del Cittadino!</h3>
                    <p>Ciao $nome $cognome,</p>
                    <p>Per completare la tua registrazione e iniziare a inviare segnalazioni, devi confermare il tuo indirizzo email cliccando sul link sottostante:</p>
                    <p><a href='$verify_link' style='padding:10px 15px; background:#add8e6; color:#000; text-decoration:none; border:1px solid #8ab8c6; font-weight:bold;'>Verifica Account Subito</a></p>
                    <p>Se non riesci a cliccare sul bottone, copia e incolla questo indirizzo nel tuo browser:<br>
                    <small><code>$verify_link</code></small></p>";
                
                $mail_sent = inviaEmailSetup($email, $subject, $html_message);

                if ($mail_sent) {
                    $host_ip = explode(':', $_SERVER['HTTP_HOST'])[0];
                    $msg = "Registrazione completata con successo!<br><br>Ti abbiamo inviato <strong>un'email di conferma all'indirizzo indicato.</strong> Controlla la posta (e il server Mailpit su <code>http://" . $host_ip . ":8025</code>) per attivare l'account prima di accedere.";
                } else {
                    $msg = "Profilo creato, ma <strong>si è verificato un errore nell'invio dell'email</strong>. Verifica che Mailpit sia in esecuzione.";
                }
            } catch (Exception $e) {
                $error = "Errore durante il salvataggio dei dati: " . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Registrazione Cittadino - Comune di Palermo';
require_once 'includes/_partials/head.php';
?>

    <main class="main-content">
        <?php 
        $headerIcon = 'fa-address-card';
        $headerTitle = 'Registrazione Cittadino';
        require_once 'includes/_partials/top_header.php';
        ?>

        <div class="container">
        <?php require_once 'includes/_partials/alerts.php'; ?>

        <?php if (!$msg): // nascondi il form se ha già completato la registrazione ?>
            <form action="registrazione.php" method="POST" id="formRegistrazione">
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" required>

                <label for="cognome">Cognome</label>
                <input type="text" id="cognome" name="cognome" required>

                <label for="codice_fiscale">Codice Fiscale</label>
                <input type="text" id="codice_fiscale" name="codice_fiscale" maxlength="16" required>

                <label for="data_nascita">Data di Nascita</label>
                <input type="date" id="data_nascita" name="data_nascita" required style="padding: 10px; border: 1px solid #ccc; font-family: Arial;">

                <label for="email"><i class="fa-solid fa-envelope"></i> Indirizzo Email</label>
                <input type="email" id="email" name="email" required style="padding: 10px; border: 1px solid #ccc; font-family: Arial;">

                <label for="password">Password (Minimo 8 caratteri, 1 maiuscola, 1 speciale)</label>
                <input type="password" id="password" name="password" required>
                
                <!-- Semafro Password -->
                <div class="pwd-leds">
                    <div class="led" id="led1"></div>
                    <div class="led" id="led2"></div>
                    <div class="led" id="led3"></div>
                    <div class="led" id="led4"></div>
                </div>
                <span id="pwdText" class="pwd-status-text">Inserisci una password...</span>

                <label for="conferma_password"><i class="fa-solid fa-check-double"></i> Conferma Password</label>
                <input type="password" id="conferma_password" name="conferma_password" required>

                <button type="submit" name="registra" id="btnSubmit" disabled style="opacity: 0.5;">
                    Registrati
                </button>
            </form>
        <?php endif; ?>
    </div>

    <script>
    $(document).ready(function() {
        const $pwdInput = $('#password');
        const $confirmInput = $('#conferma_password');
        const $btnSubmit = $('#btnSubmit');
        const $form = $('#formRegistrazione');
        
        const $leds = $('.led');
        const $pwdText = $('#pwdText');

        let isPasswordSecure = false;

        function resetLeds() {
            $leds.removeClass('rossa gialla verde-chiaro verde-scuro');
        }

        function getSpecialCharCount(str) {
            const matches = str.match(/[^a-zA-Z0-9]/g);
            return matches ? matches.length : 0;
        }

        function evaluatePassword() {
            const pwd = $pwdInput.val();
            resetLeds();
            
            const lengthOk = pwd.length >= 8;
            const hasUpper = /[A-Z]/.test(pwd);
            const specialCount = getSpecialCharCount(pwd);
            const hasMinSpecial = specialCount >= 1;
            
            isPasswordSecure = false;

            if (pwd.length === 0) {
                $pwdText.text("Inserisci una password...").css('color', '#333');
                validateForm();
                return;
            }

            let score = 0;
            if (lengthOk) score++;
            if (hasUpper) score++;
            if (hasMinSpecial) score++;
            if (pwd.length >= 12 && specialCount > 1 && hasUpper) score++; 

            if (!lengthOk || score === 1 || (!hasUpper && !hasMinSpecial)) {
                $($leds[0]).addClass('rossa');
                $pwdText.text("Debole: Minimo 8 caratteri, 1 Maiuscola e 1 Speciale richiesto!").css('color', '#d32f2f');
            } else if (lengthOk && score === 2) {
                $($leds[0]).addClass('gialla');
                $($leds[1]).addClass('gialla');
                $pwdText.text("Mediocre: Aggiungi un carattere maiuscolo o speciale mancante.").css('color', '#fbc02d');
            } else if (lengthOk && hasUpper && hasMinSpecial && score === 3) {
                $leds.slice(0, 3).addClass('verde-chiaro');
                $pwdText.text("Sicura: Password accettabile.").css('color', '#689f38');
                isPasswordSecure = true;
            } else if (score >= 4) {
                $leds.addClass('verde-scuro');
                $pwdText.text("Molto Sicura: Eccellente.").css('color', '#388e3c');
                isPasswordSecure = true;
            }
            validateForm();
        }

        function validateForm() {
            let allFilled = true;
            $form.find('input[required]').each(function() {
                if (!$(this).val().trim()) {
                    allFilled = false;
                }
            });

            const passwordsMatch = ($pwdInput.val() === $confirmInput.val()) && $pwdInput.val() !== "";
            const cfValid = $('#codice_fiscale').val().length === 16;

            if (allFilled && isPasswordSecure && passwordsMatch && cfValid) {
                $btnSubmit.prop('disabled', false).css({opacity: 1, cursor: 'pointer'});
            } else {
                $btnSubmit.prop('disabled', true).css({opacity: 0.5, cursor: 'not-allowed'});
            }
        }

        $form.on('input', validateForm);
        $pwdInput.on('input', evaluatePassword);
        
        $form.on('submit', function(e) {
            if ($pwdInput.val() !== $confirmInput.val()) {
                e.preventDefault();
                alert('Le password non coincidono!');
            }
        });
    });
    </script>
    <?php require_once 'includes/_partials/footer.php'; ?>
