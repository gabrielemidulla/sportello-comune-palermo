function toggleVote(id) {
    const $btn = $('#btn-' + id);
    const $countSpan = $('#count-' + id);
    
    $.post('ajax_handler.php', {
        action: 'toggle_upvote',
        segnalazione_id: id
    }, function(data) {
        if (data.success) {
            $countSpan.text(data.new_count);
            if (data.status === 'added') {
                $btn.addClass('voted').css({backgroundColor: '#4caf50', color: '#fff'})
                    .html('<i class="fa-solid fa-check"></i> Rimuovi');
            } else {
                $btn.removeClass('voted').css({backgroundColor: '', color: ''})
                    .html('<i class="fa-solid fa-arrow-up"></i> Vota');
            }
        } else {
            alert(data.message);
        }
    }, 'json').fail(function() {
        console.error('Errore durante il voto');
    });
}

// Assuming window.currentPage is set before this file is loaded
let currentSort = 'upvotes';
let currentOrder = 'DESC';
let currentStradaId = null;

$(document).ready(function() {
    // Custom Combobox for Filtering
    const $filterBtn = $('#filter-address-btn');
    const $filterDropdown = $('#filter-dropdown');
    const $filterSearch = $('#filter-search');
    const $filterResults = $('#filter-results');
    const $filterText = $('#filter-address-text');
    const $hiddenStradaId = $('#filter-strada-id');
    const $clearBtn = $('#clear-filter-btn');

    $filterBtn.on('click', function(e) {
        e.stopPropagation();
        $filterDropdown.toggle();
        if ($filterDropdown.is(':visible')) {
            $filterSearch.focus();
        }
    });

    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.custom-combobox').length) {
            $filterDropdown.hide();
        }
    });

    $filterSearch.on('input', function() {
        const term = $(this).val();
        if (term.length < 2) {
            $filterResults.empty();
            return;
        }

        $.getJSON('ajax_handler.php', {
            action: 'search_strade',
            term: term
        }, function(data) {
            $filterResults.empty();
            if (data.length === 0) {
                $filterResults.append('<div class="combobox-no-results">Nessuna strada trovata</div>');
            } else {
                data.forEach(item => {
                    const $item = $('<div class="combobox-item"></div>').text(item.label);
                    $item.on('click', function() {
                        $filterText.text(item.value);
                        currentStradaId = item.id;
                        $hiddenStradaId.val(item.id);
                        $clearBtn.show();
                        $filterDropdown.hide();
                        loadBacheca(1);
                    });
                    $filterResults.append($item);
                });
            }
        });
    });

    $clearBtn.on('click', function(e) {
        e.stopPropagation();
        currentStradaId = null;
        $hiddenStradaId.val('');
        $filterText.text('Tutte');
        $filterSearch.val('');
        $filterResults.empty();
        $clearBtn.hide();
        loadBacheca(1);
    });

    // Initialize icons
    updateSortIcons();
});

// Expose these globally if inline HTML triggers them
window.changeSort = function(field) {
    if (currentSort === field) {
        currentOrder = (currentOrder === 'DESC') ? 'ASC' : 'DESC';
    } else {
        currentSort = field;
        currentOrder = 'DESC'; // Default al più recente/più votato
    }
    updateSortIcons();
    loadBacheca(1);
}

function updateSortIcons() {
    // Reset icons
    $('#sort-icon-upvotes, #sort-icon-indirizzo, #sort-icon-data').html('');
    
    const icon = (currentOrder === 'DESC') ? ' <i class="fa-solid fa-caret-down"></i>' : ' <i class="fa-solid fa-caret-up"></i>';
    $('#sort-icon-' + currentSort).html(icon);
}

window.changePage = function(page) {
    loadBacheca(page);
}

function loadBacheca(page) {
    const $body = $('#bacheca-body');
    const $loader = $('#loader');
    const $pagination = $('#pagination-container');
    
    $body.addClass('active');
    $loader.show();
    
    $.post('ajax_handler.php', {
        action: 'load_bacheca',
        page: page,
        sort: currentSort,
        order: currentOrder,
        strada_id: currentStradaId
    }, function(data) {
        if (data.success) {
            $body.html(data.html);
            $pagination.html(data.pagination);
            window.currentPage = page;
            window.history.pushState({page: page, sort: currentSort, order: currentOrder, strada_id: currentStradaId}, "", "?page=" + page + "&sort=" + currentSort + "&order=" + currentOrder + (currentStradaId ? "&strada_id=" + currentStradaId : ""));
        }
    }, 'json').fail(function() {
        console.error('Errore caricamento');
    }).always(function() {
        $body.removeClass('active');
        $loader.hide();
    });
}
