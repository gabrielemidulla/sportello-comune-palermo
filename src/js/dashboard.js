let currentPage = 1;

function loadOperatorBacheca(page = 1) {
    currentPage = page;
    const statusFilter = $('#status-filter').val();
    
    $('.table-container').addClass('active');
    $('#loader').show();

    $.post('ajax_handler.php', {
        action: 'load_operator_bacheca',
        page: page,
        stato: statusFilter
    }, function(response) {
        $('.table-container').removeClass('active');
        $('#loader').hide();
        
        if (response.success) {
            $('#operator-body').html(response.html);
            $('#pagination-container').html(response.pagination);
        } else {
            alert(response.message || 'Errore durante il caricamento');
        }
    }, 'json');
}

window.updateStatus = function(id, newStatus) {
    $('#loader').show();
    $.post('ajax_handler.php', {
        action: 'update_status',
        id: id,
        stato: newStatus
    }, function(response) {
        $('#loader').hide();
        if (response.success) {
            // Ricarica la pagina corrente per mostrare i badge aggiornati
            loadOperatorBacheca(currentPage);
        } else {
            alert(response.message || 'Errore durante l\'aggiornamento');
        }
    }, 'json');
}

window.changePage = function(page) {
    loadOperatorBacheca(page);
}

// Caricamento iniziale
$(document).ready(function() {
    loadOperatorBacheca(1);
});
