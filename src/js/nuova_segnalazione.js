$(document).ready(function() {
    const $trigger = $('#combobox-trigger');
    const $dropdown = $('#combobox-dropdown');
    const $search = $('#combobox-search');
    const $results = $('#combobox-results');
    const $hiddenIndirizzo = $('#indirizzo');
    const $hiddenStradaId = $('#strada_id');
    const $selectedText = $('#selected-address-text');
    const $submitBtn = $('#btn-submit');
    const $textarea = $('#descrizione');
    const $counter = $('#char-counter');

    // Toggle dropdown
    $trigger.on('click', function() {
        $dropdown.toggle();
        if ($dropdown.is(':visible')) {
            $search.focus();
        }
    });

    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.custom-combobox').length) {
            $dropdown.hide();
        }
    });

    // Search logic
    let searchTimeout;
    $search.on('input', function() {
        clearTimeout(searchTimeout);
        const term = $(this).val();

        if (term.length < 2) {
            $results.empty();
            return;
        }

        searchTimeout = setTimeout(function() {
            $results.html('<div class="combobox-no-results"><i class="fa-solid fa-spinner fa-spin"></i> Ricerca in corso...</div>');
            
            $.getJSON('ajax_handler.php', { action: 'search_strade', term: term }, function(data) {
                $results.empty();
                if (data.length === 0) {
                    $results.append('<div class="combobox-no-results">Nessun indirizzo trovato</div>');
                } else {
                    data.forEach(function(item) {
                        const $item = $('<div class="combobox-item"></div>').text(item.label);
                        $item.on('click', function() {
                            $selectedText.text(item.label);
                            $hiddenIndirizzo.val(item.value);
                            $hiddenStradaId.val(item.id);
                            $dropdown.hide();
                            checkValidation();
                        });
                        $results.append($item);
                    });
                }
            });
        }, 300);
    });

    // Character counter
    $textarea.on('input', function() {
        const length = $(this).val().length;
        $counter.text(length + ' / 500');
        
        if (length >= 500) {
            $counter.addClass('limit-reached');
        } else {
            $counter.removeClass('limit-reached');
        }
        checkValidation();
    });

    // Validation logic
    function checkValidation() {
        const addressSelected = $hiddenIndirizzo.val().trim() !== '';
        const descPopulated = $textarea.val().trim() !== '';
        
        if (addressSelected && descPopulated) {
            $submitBtn.prop('disabled', false);
        } else {
            $submitBtn.prop('disabled', true);
        }
    }
});
