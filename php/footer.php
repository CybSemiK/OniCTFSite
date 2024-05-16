<?php
require_once "db.php";

// Récupérer les paramètres de personnalisation depuis la base de données
$query = "SELECT theme_color, team_name FROM settings ORDER BY id DESC LIMIT 1";
$result = $conn->query($query);
$settings = $result->fetch_assoc();

$themeColor = $settings['theme_color'] ?? '#007bff'; // Couleur par défaut si non définie
$teamName = $settings['team_name'] ?? 'Nom de votre équipe';
?>


<footer class="footer" style="background-color: <?= htmlspecialchars($themeColor); ?>">
    &copy; <?= date('Y'); ?> <?= htmlspecialchars($teamName); ?>. Tous droits réservés.
</footer>
