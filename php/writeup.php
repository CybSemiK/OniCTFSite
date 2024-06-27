<?php

session_start();
require_once 'db.php';
if (isset($_SESSION['error'])) {
    echo "<script>alert('" . addslashes($_SESSION['error']) . "');</script>";
    unset($_SESSION['error']);  // Supprimez le message après affichage pour éviter qu'il réapparaisse lors du rechargement de la page
}

// Assurez-vous que l'ID du challenge est spécifié
if (!isset($_GET["challenge_id"])) {
    die("ID du challenge non spécifié.");
}

$challengeId = $_GET["challenge_id"];

// Récupérer les informations du challenge
$stmt = $conn->prepare("SELECT * FROM challenges WHERE id = ?");
$stmt->bind_param("i", $challengeId);
$stmt->execute();
$result = $stmt->get_result();
if ($challenge = $result->fetch_assoc()) {
    $challengeName = $challenge["nom"];
} else {
    die("Challenge non trouvé.");
}

// Récupérez les write-ups liés au challenge
$stmt = $conn->prepare("SELECT * FROM writeups WHERE challenge_id = ?");
$stmt->bind_param("i", $challengeId);
$stmt->execute();
$writeupsResult = $stmt->get_result();
$writeups = [];
while ($row = $writeupsResult->fetch_assoc()) {
    $writeups[] = $row;
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
    <title>Write-ups pour <?= htmlspecialchars($challengeName); ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        :root { --theme-color: <?= $themeColor; ?>; }
    body { background-color: #f4f4f4; }
    .navbar-custom { background-color: var(--theme-color); }
    .navbar-custom .navbar-brand, .navbar-custom .nav-link { color: white; }
    .header-challenge { background: var(--theme-color); color: white; padding: 20px; border-radius: 8px; margin-top: 10px; margin-bottom: 30px; text-align: center; }
    .header-challenge h1 { margin-top: 0; }
    .writeup-card { background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,.1); }
    .writeup-info { font-size: 14px; margin-bottom: 15px; color: #666; }
    .writeup-actions a { margin-right: 10px; }
    .footer { background-color: var(--theme-color); color: white; padding: 20px 0; text-align: center; margin-top: 40px; }
    .back-link { font-weight: bold; color: var(--theme-color); display: inline-block; margin-top: 20px; }
    .back-link i { margin-right: 5px; }
    .upload-form { background-color: white; padding: 20px; border-radius: 8px; border: 2px solid var(--theme-color); margin-bottom: 20px; }
    .btn { border-radius: 0.25rem; transition: background-color 0.3s, border-color 0.3s, color 0.3s; }
    .btn-primary { background-color: #fff; border-color: var(--theme-color); color: var(--theme-color); }
    .btn-primary:hover { background-color: var(--theme-color); color: #fff; }
    .btn-danger:hover { background-color: #dc3545; color: #fff; }
    body.night-mode { background-color: #333; color: white; }
    .navbar-custom.night-mode { background-color: #222; }
    .footer.night-mode { background-color: #222; }
    body.night-mode .card, .writeup-info { background-color: #ffffff; color: black; }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container">
    <div class="header-challenge">
        <h1>Write-ups pour <?= htmlspecialchars($challengeName); ?></h1>
    </div>
    <div class="upload-form">
        <form action="upload_writeup.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="challenge_id" value="<?= htmlspecialchars($challengeId, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-group">
                <label for="writeup_file">Upload Write-up:</label>
                <input type="file" id="writeup_file" name="writeup_file" class="form-control-file"><br>
                <button type="submit" class="btn btn-primary">Upload Write-up</button>
            </div>
        </form>
    </div>
    <?php if (count($writeups) > 0): ?>
        <?php foreach ($writeups as $writeup): ?>
            <div class="writeup-card">
                <p class="writeup-info"><strong>Nom du fichier :</strong> <?= basename($writeup["writeup_path"]); ?></p>
                <div class="writeup-actions">
                    <a href="<?= htmlspecialchars($writeup["writeup_path"]); ?>" download class="btn btn-primary">Télécharger</a>
                    <a href="view_writeup.php?writeup_id=<?= $writeup['id']; ?>" class="btn btn-primary">Voir</a>
                    <a href="delete_writeup.php?writeup_id=<?= htmlspecialchars(urlencode($writeup['id'])); ?>&challenge_id=<?= htmlspecialchars(urlencode($challengeId)); ?>" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce write-up ? Cette action est irréversible.');">Supprimer</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-info">Aucun write-up disponible.</div>
    <?php endif; ?>

    <a href="challenge.php?id=<?= htmlspecialchars($challengeId); ?>" class="back-link"><i class="fas fa-arrow-left"></i> Retour au challenge</a>
</div>
<?php include 'footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="js/themeSwitcher.js"></script>
</body>
</html>
