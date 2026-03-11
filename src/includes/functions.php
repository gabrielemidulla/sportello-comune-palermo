<?php

use Lib\Database;

/**
 * Standardize JSON responses
 */
function sendResponse($success, $data = [], $message = '') {
    header('Content-Type: application/json');
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $data));
    exit;
}

/**
 * Render a signaling row for the bacheca
 */
function renderSegnalazioneRow($row, $corrente_cittadino) {
    $is_creator = ($corrente_cittadino > 0 && $corrente_cittadino == $row['creatore_id']);
    $voted = (isset($row['user_has_upvoted']) && $row['user_has_upvoted'] > 0);
    $is_resolved = ($row['stato'] === 'Risolto');
    
    $badge_class = 'bg-attesa';
    if ($row['stato'] == 'Presa in carico') $badge_class = 'bg-carico';
    elseif ($row['stato'] == 'Risolto') $badge_class = 'bg-risolto';

    ob_start();
    ?>
    <tr>
        <td class="upvote-cell">
            <span class="upvote-count" id="count-<?php echo $row['id']; ?>"><?php echo $row['upvotes']; ?></span>
            <?php if ($corrente_cittadino > 0): ?>
                <?php if ($is_creator): ?>
                    <span class="creator-label"><i class="fa-solid fa-user-pen"></i> Tua</span>
                <?php else: ?>
                    <?php 
                        if ($is_resolved) {
                            $btn_text = '<i class="fa-solid fa-lock"></i> Chiusa';
                            $btn_style = 'background-color: #eee; color: #999; border-color: #ddd; cursor: not-allowed;';
                            $onclick = '';
                        } else {
                            $btn_text = $voted ? '<i class="fa-solid fa-check"></i> Rimuovi' : '<i class="fa-solid fa-arrow-up"></i> Vota';
                            $btn_style = $voted ? 'background-color: #4caf50; color: #fff;' : '';
                            $onclick = 'onclick="toggleVote('.$row['id'].')"';
                        }
                    ?>
                    <button class="btn-upvote <?php echo $voted ? 'voted' : ''; ?>" 
                            <?php echo $onclick; ?> 
                            id="btn-<?php echo $row['id']; ?>"
                            style="<?php echo $btn_style; ?>"
                            <?php echo $is_resolved ? 'disabled' : ''; ?>>
                        <?php echo $btn_text; ?>
                    </button>
                <?php endif; ?>
            <?php else: ?>
                <a href="login_cittadino.php" class="login-to-vote"><i class="fa-solid fa-lock"></i> Login</a>
            <?php endif; ?>
        </td>
        <td>
            <strong style="font-size: 15px; color: #333;"><i class="fa-solid fa-tag"></i> <?php echo htmlspecialchars($row['categoria']); ?></strong><br>
            <small><?php echo htmlspecialchars($row['descrizione']); ?></small>
        </td>
        <td><?php echo htmlspecialchars($row['indirizzo']); ?></td>
        <td><?php echo date('d/m/Y H:i', strtotime($row['data_inserimento'])); ?></td>
        <td style="vertical-align: middle; text-align: center;">
            <div class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($row['stato']); ?></div><br>
        </td>
    </tr>
    <?php
    return ob_get_clean();
}

/**
 * Global Exception Handler
 */
function globalExceptionHandler($e) {
    error_log("Unhandled Exception: " . $e->getMessage());
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        sendResponse(false, [], "Si è verificato un errore imprevisto.");
    } else {
        echo "<div style='padding: 20px; background: #fee; border: 1px solid #fcc; color: #c00; margin: 20px; font-family: sans-serif;'>
                <strong>Errore del sistema:</strong> Si è verificato un errore durante l'elaborazione della richiesta.
              </div>";
    }
    exit;
}

set_exception_handler('globalExceptionHandler');
