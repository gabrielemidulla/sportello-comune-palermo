<?php
$current_page = basename($_SERVER['SCRIPT_NAME']);
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <img src="/img/logo.png" alt="Logo PA" class="sidebar-logo">
        <h1>Sportello del Cittadino</h1>
        <h2>Comune di Palermo</h2>
    </div>
    
    <?php if(isset($_SESSION['cittadino_id'])): ?>
        <div class="sidebar-user">
            <i class="fa-solid fa-user-check"></i> Ciao, <?php echo htmlspecialchars($_SESSION['nome_cittadino'] . ' ' . $_SESSION['cognome_cittadino']); ?>
        </div>
    <?php elseif(isset($_SESSION['dipendente_id'])): ?>
        <div class="sidebar-user" style="background-color: rgba(255,255,255,0.2);">
            <i class="fa-solid fa-user-shield"></i> Operatore:<br><?php echo htmlspecialchars($_SESSION['matricola']); ?>
        </div>
    <?php endif; ?>

    <nav class="sidebar-nav">
        <div class="sidebar-heading">Segnalazioni</div>
        <!-- Lato Cittadino Pubblico -->
        <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-table-list"></i> Bacheca Pubblica
        </a>
        <a href="nuova_segnalazione.php" class="<?php echo ($current_page == 'nuova_segnalazione.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-pen-to-square"></i> Nuova Segnalazione
        </a>
        
        <div class="sidebar-heading" style="margin-top: 20px;">Area Riservata</div>
        <?php if(isset($_SESSION['cittadino_id'])): ?>
            <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Esci</a>
        <?php elseif(!isset($_SESSION['dipendente_id'])): ?>
            <a href="login_cittadino.php" class="<?php echo ($current_page == 'login_cittadino.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-right-to-bracket"></i> Accesso Cittadini
            </a>
            <a href="registrazione.php" class="<?php echo ($current_page == 'registrazione.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-user-plus"></i> Registrati
            </a>
        <?php endif; ?>
        
        <!-- Lato Admin -->
        <?php if(isset($_SESSION['dipendente_id'])): ?>
            <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>" style="margin-top:auto; background-color: #901616;">
                <i class="fa-solid fa-folder-open"></i> Cruscotto Pratiche
            </a>
            <a href="logout.php" style="background-color: #901616;">
                <i class="fa-solid fa-right-from-bracket"></i> Esci (Operatore)
            </a>
        <?php else: ?>
            <a href="login.php" class="<?php echo ($current_page == 'login.php') ? 'active' : ''; ?>" style="margin-top:auto; background-color: #901616;">
                <i class="fa-solid fa-user-tie"></i> Area Operatori
            </a>
        <?php endif; ?>
    </nav>
</aside>
