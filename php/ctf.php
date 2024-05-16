<?php
session_start();
require_once "db.php";

// Assurez-vous d'être connecté et d'avoir le rôle approprié
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || ($_SESSION["role"] !== 'admin' && $_SESSION["role"] !== 'user')) {
    header("location: login.php");
    exit;
}
$query = "SELECT theme_color, team_name FROM settings ORDER BY id DESC LIMIT 1";
$result = $conn->query($query);
$settings = $result->fetch_assoc();

$themeColor = $settings['theme_color'] ?? '#007bff';
$teamName = $settings['team_name'] ?? 'Nom de votre équipe';


// Fonction pour afficher les événements
function afficherEvenements($conn, $statut) {
    $sql = $conn->prepare("SELECT id, nom, description, dateDebut FROM ctf_events WHERE statut = ? ORDER BY dateDebut ASC");
    $sql->bind_param("s", $statut);
    $sql->execute();
    $result = $sql->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<div class='card mb-3'>";
            echo "<div class='card-body'>";
            echo "<h5 class='card-title'>" . htmlspecialchars($row["nom"]) . "</h5>";
            echo "<p class='card-text'>" . htmlspecialchars($row["description"]) . "</p>";
            echo "<a href='event.php?id=" . $row["id"] . "' class='btn btn-primary' style='background-color: $themeColor; border-color: $themeColor;'>Voir plus</a>";
            echo "</div>";
            echo "</div>";
        }
    } else {
        echo "<p>Aucun événement trouvé.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Événements CTF</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        :root {--theme-color: <?= $themeColor; ?>;}
        body { background-color: #f4f4f4; }
        .navbar-custom { background-color: #007bff; }
        .navbar-custom .navbar-brand, .navbar-custom .nav-link { color: white; }
        .header-ctf { background: var(--theme-color); color: white; padding: 20px; border-radius: 8px; margin-top: 10px; margin-bottom: 30px; text-align: center;}
        .header-ctf h1 { margin-top: 0; }
        .event-section { padding: 40px 0; }
        .event-card { box-shadow: 0 4px 6px rgba(0,0,0,.1); }
        .event-card:hover { transform: scale(1.03); }
        .event-image { height: 200px; object-fit: cover; }
        .event-title { font-size: 1.25rem; }
        .event-description { color: #666; }
        .footer { background-color: #007bff; color: white; padding: 20px 0; text-align: center; margin-top: 40px; }
        body.night-mode { background-color: #333; color: white; }
        .navbar-custom.night-mode { background-color: #222; }
        .footer.night-mode { background-color: #222; }
        body.night-mode .card { background-color: #ffffff; color: black; }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container">
    <h1 class="header-ctf">Événements de l'équipe <?= htmlspecialchars($teamName); ?></h1>

    <section class="event-section">
        <h3>Événements Prévisionnels</h3>
        <div class="card-deck">
            <?php afficherEvenements($conn, 'prévisionnel', $themeColor); ?>
        </div>
    </section>

    <section class="event-section">
        <h3>Événements En Cours</h3>
        <div class="card-deck">
            <?php afficherEvenements($conn, 'en cours', $themeColor); ?>
        </div>
    </section>

    <section class="event-section">
        <h3>Événements Passés</h3>
        <div class="card-deck">
            <?php afficherEvenements($conn, 'passé', $themeColor); ?>
        </div>
    </section>
</div>

<?php include 'footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="js/themeSwitcher.js"></script>
</body>
</html>
