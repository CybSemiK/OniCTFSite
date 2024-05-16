<?php
require_once "db.php";

// Récupérer les paramètres de personnalisation depuis la base de données
$query = "SELECT logo_path, theme_color FROM settings ORDER BY id DESC LIMIT 1";
$result = $conn->query($query);
$settings = $result->fetch_assoc();

$logoPath = $settings['logo_path'] ?? 'path/to/default/logo.png';
$themeColor = $settings['theme_color'] ?? '#007bff'; // Couleur par défaut si non définie
?>

<nav class="navbar navbar-expand-lg navbar-custom" style="background-color: <?= htmlspecialchars($themeColor); ?>">
    <a class="navbar-brand" href="ctf.php">
        <?php if ($logoPath): ?>
            <img src="<?= htmlspecialchars($logoPath); ?>" alt="Logo" style="height: 80px;">
        <?php else: ?>
            CTF Events
        <?php endif; ?>
    </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ml-auto">
            <li class="nav-item active">
                <a class="nav-link" href="scoreboard.php">Scoreboard</a>
            </li>
            <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && $_SESSION["role"] === 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link" href="admin_panel.php">Panneau Admin</a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link" href="logout.php">Déconnexion</a>
            </li>
        </ul>
        <button id="toggleModeBtn" class="btn"><i class="fas fa-moon"></i></button>
    </div>
</nav>
