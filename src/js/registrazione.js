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
