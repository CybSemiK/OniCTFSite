<?php
require_once 'db.php';

if (!isset($_GET["id"])) {
    die("ID du challenge non spécifié.");
}

$challengeId = $_GET["id"];

// Récupérez les informations du challenge, y compris l'ID de la catégorie
$stmt = $conn->prepare("SELECT c.nom, c.categorie_id FROM challenges c WHERE c.id = ?");
$stmt->bind_param("i", $challengeId);
$stmt->execute();
$result = $stmt->get_result();
if ($challenge = $result->fetch_assoc()) {
    $challengeName = $challenge["nom"];
    $categorieId = $challenge["categorie_id"];
} else {
    die("Challenge non trouvé.");
}

// Récupérez les rapports liés au challenge
$stmt = $conn->prepare("SELECT r.id, u.username, r.fichier FROM rapports r JOIN users u ON r.user_id = u.id WHERE r.challenge_id = ?");
$stmt->bind_param("i", $challengeId);
$stmt->execute();
$rapportsResult = $stmt->get_result();
$rapports = [];
while ($row = $rapportsResult->fetch_assoc()) {
    $rapports[] = $row;
}

$query = "SELECT theme_color FROM settings ORDER BY id DESC LIMIT 1";
$result = $conn->query($query);
$settings = $result->fetch_assoc();

$themeColor = $settings['theme_color'] ?? '#007bff';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapports pour <?= htmlspecialchars($challengeName); ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        :root {--theme-color: <?= $themeColor; ?>;}
        body { background-color: #f4f4f4; }
        .navbar-custom { background-color: #007bff; }
        .navbar-custom .navbar-brand, .navbar-custom .nav-link { color: white; }
        .header-challenge { background: var(--theme-color); color: white; padding: 20px; border-radius: 8px; margin-top: 10px; margin-bottom: 30px;  text-align: center;}
        .header-challenge h1 { margin-top: 0; }
        .rapport-card { background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,.1); }
        .rapport-title { font-weight: bold; margin-bottom: 10px; }
        .rapport-info { font-size: 14px; margin-bottom: 15px; color: #666; }
        .rapport-actions a { margin-right: 10px; }
        .back-link { font-weight: bold; color: var(--theme-color); display: inline-block; margin-top: 20px; }
        .back-link i { margin-right: 5px; }
        .footer { background-color: #007bff; color: white; padding: 20px 0; text-align: center; margin-top: 40px; }
        body.night-mode { background-color: #333; color: white; }
        .navbar-custom.night-mode { background-color: #222; }
        .footer.night-mode { background-color: #222; }
        body.night-mode .card, .rapport-title { background-color: #ffffff; color: black; }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>


<div class="container">
    <div class="header-challenge">
        <h1>Rapports pour <?= htmlspecialchars($challengeName); ?></h1>
    </div>

    <?php if (count($rapports) > 0): ?>
        <?php foreach ($rapports as $rapport): ?>
            <div class="rapport-card">
                <h5 class="rapport-title">Rapport de <?= htmlspecialchars($rapport["username"]); ?></h5>
                <p class="rapport-info"><strong>Nom du fichier :</strong> <?= basename($rapport["fichier"]); ?></p>
                <div class="rapport-actions">
                    <a href="view_rapport.php?rapport_id=<?= $rapport["id"]; ?>&action=view" class="btn btn-primary">Consulter</a>
                    <a href="view_rapport.php?rapport_id=<?= $rapport["id"]; ?>&action=download" class="btn btn-secondary">Télécharger</a>
                    <a href="delete_rapport.php?rapport_id=<?= $rapport['id']; ?>" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce rapport ? Cette action est irréversible.')">Supprimer</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-info">Aucun rapport disponible.</div>
    <?php endif; ?>

    <a href="categorie.php?categorie_id=<?= htmlspecialchars($categorieId); ?>" class="back-link"><i class="fas fa-arrow-left"></i> Retour à la catégorie</a>
</div>
<?php include 'footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="js/themeSwitcher.js"></script>
</body>
</html>
