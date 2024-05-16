<?php
session_start();
require_once "db.php";

// Vérification de la connexion de l'utilisateur
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Vérification que l'ID de l'utilisateur est passé en paramètre
if (!isset($_GET["user_id"])) {
    die("ID de l'utilisateur non spécifié.");
}

$userId = $_GET["user_id"];

// Préparation du titre de la page avec le nom de l'utilisateur
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $username = htmlspecialchars($row["username"]);
} else {
    $username = "Utilisateur Inconnu";
}

// Préparation des données pour le graphique radar
$categories = ["forensic", "web", "fullpwn", "reverse", "pwn", "osint", "Cryptographie"];
$categoryCounts = array_fill_keys($categories, 0);

$sql = "SELECT c.nom AS categorie, COUNT(*) AS count
        FROM challenges ch
        JOIN categories c ON ch.categorie_id = c.id
        WHERE ch.est_complet = 1 AND ch.en_cours_par = ?
        GROUP BY c.nom";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    if (in_array($row["categorie"], $categories)) {
        $categoryCounts[$row["categorie"]] = $row["count"];
    }
}

$dataPoints = array_values($categoryCounts);
$totalFlags = array_sum($dataPoints);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Statistiques de <?= $username ?></title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #f4f4f4; }
        .navbar-custom { background-color: #007bff; color: white; }
        .navbar-custom .navbar-brand, .navbar-custom .nav-link { color: white; }
        .container { margin-top: 20px; }
        .footer { background-color: #007bff; color: white; padding: 20px 0; text-align: center; margin-top: 40px; }
        body.night-mode { background-color: #333; color: white; }
        .navbar-custom.night-mode { background-color: #222; }
        .footer.night-mode { background-color: #222; }
        body.night-mode .card { background-color: #ffffff; color: black; }
        .night-mode #radarChart { background-color: white;}
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container">
    <h2>Statistiques de <?= $username ?></h2>
    <p>Nombre total de flags : <?= $totalFlags ?></p>
    <canvas id="radarChart" width="300" height="300"></canvas>
</div>
<?php include 'footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="js/themeSwitcher.js"></script>
<script>
    var categories = <?= json_encode($categories) ?>;
    var dataPoints = <?= json_encode($dataPoints) ?>;
</script>
<script src="js/radarChart.js"></script>
</body>
</html>
