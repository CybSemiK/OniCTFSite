<?php
session_start();
include 'navbar.php';
require_once "db.php"; // Assurez-vous que ce chemin est correct

// Récupérer les valeurs actuelles pour les maintenir si elles ne sont pas mises à jour
$currentSettingsQuery = $conn->query("SELECT logo_path, theme_color, team_name FROM settings ORDER BY id DESC LIMIT 1");
$currentSettings = $currentSettingsQuery->fetch_assoc();

// Définir des valeurs par défaut en cas de non-existence
$themeColor = $currentSettings['theme_color'] ?? '#007bff';
$teamName = $currentSettings['team_name'] ?? 'Nom de votre équipe';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Paramètres d'apparence</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f4f4; }
        .navbar-custom { background-color: #007bff; }
        .navbar-custom .navbar-brand, .navbar-custom .nav-link { color: white; }
        .footer { background-color: #007bff; color: white; padding: 20px 0; text-align: center; margin-top: 40px; }
        body.night-mode { background-color: #333; color: white; }
        .navbar-custom.night-mode { background-color: #222; }
        .footer.night-mode { background-color: #222; }
    </style>
</head>
<body>
<div class="container">
    <h2>Personnaliser l'apparence du site</h2>
    <form action="upload_settings.php" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="theme_color">Couleur du thème :</label>
            <input type="color" id="theme_color" name="theme_color" value="<?= htmlspecialchars($themeColor); ?>" class="form-control">
        </div>
        <div class="form-group">
            <label for="surprise">Activer le mode Surprise :</label>
            <input type="checkbox" id="surprise" name="surprise" class="form-check-input">
        </div>
        <div class="form-group">
            <label for="team_name">Nom de l'équipe :</label>
            <input type="text" id="team_name" name="team_name" value="<?= htmlspecialchars($teamName); ?>" class="form-control">
        </div>
        <div class="form-group">
            <label for="logo">Logo de l'équipe :</label>
            <input type="file" id="logo" name="logo" class="form-control-file">
        </div>
        <button type="submit" class="btn btn-primary">Sauvegarder les changements</button>
    </form>
</div>
<?php include 'footer.php'; ?>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="js/themeSwitcher.js"></script>
</body>
</html>
