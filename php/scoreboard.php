<?php
session_start();
require_once "db.php";

// Vérifiez l'authentification
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Récupération de la couleur du thème depuis la table des paramètres
$query = "SELECT theme_color FROM settings ORDER BY id DESC LIMIT 1";
$result = $conn->query($query);
$settings = $result->fetch_assoc();
$themeColor = $settings['theme_color'] ?? '#007bff'; // Utiliser une couleur par défaut si non définie

// Requête pour obtenir les scores
$sql = "SELECT u.id, u.username, COUNT(c.id) AS score
        FROM users u
        LEFT JOIN challenges c ON u.id = c.en_cours_par AND c.est_complet = 1
        WHERE u.username != 'admin'
        GROUP BY u.id
        ORDER BY score DESC, u.username ASC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
$scores = [];
while ($row = $result->fetch_assoc()) {
    $scores[] = $row;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau des scores</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
    :root {--theme-color: <?= $themeColor; ?>;}
    body { background-color: #f4f4f4; }
    .navbar-custom { background-color: #007bff; color: white; }
    .navbar-custom .navbar-brand, .navbar-custom .nav-link { color: white; }
    .header-score { background: var(--theme-color); color: white; padding: 20px; border-radius: 8px; margin-top: 10px; margin-bottom: 30px; text-align: center;}
    .header-score h1 { margin-top: 0; }
    .container { margin-top: 20px; }
    .footer { background-color: #007bff; color: white; padding: 20px 0; text-align: center; margin-top: 40px; }
    .table-hover tbody tr:hover { background-color: #f5f5f5; }
    body.night-mode { background-color: #333; color: white; }
    .navbar-custom.night-mode { background-color: #222; }
    .footer.night-mode { background-color: #222; }
    body.night-mode .card { background-color: #ffffff; color: black; }
    body.night-mode .table {
        color: #ffffff;
        background-color: #222;
    }
    body.night-mode .table-hover tbody tr:hover {
        background-color: #444; 
    }
    body.night-mode thead th {
        color: #ccc;
    }
    body.night-mode tbody tr {
        border-bottom: 1px solid #555;
    }
    body.night-mode a {
        color: #4db8ff;
    }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container">
    <h1 class="header-score">Tableau des Scores</h1>
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>Nom d'utilisateur</th>
                <th>Score</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($scores as $score): ?>
                <tr>
                    <td><a href="user_stats.php?user_id=<?= $score['id']; ?>"><?= htmlspecialchars($score['username']); ?></a></td>
                    <td><?= $score['score']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="js/themeSwitcher.js"></script>
</body>
</html>
