<?php
session_start();
require_once "db.php";
date_default_timezone_set('Europe/Paris');
// Vérification de l'ID de la catégorie passé en paramètre
if (!isset($_GET["categorie_id"])) {
    echo "ID de catégorie non spécifié.";
    exit;
}

$categorieId = $_GET["categorie_id"];

// Récupération des informations de la catégorie
$sqlCategorie = $conn->prepare("SELECT * FROM categories WHERE id = ?");
$sqlCategorie->bind_param("i", $categorieId);
$sqlCategorie->execute();
$categorieResult = $sqlCategorie->get_result();
if ($categorie = $categorieResult->fetch_assoc()) {
    $eventId = $categorie['event_id'];
}
if (!$categorie) {
    echo "Catégorie non trouvée.";
    exit;
}

// Récupération des challenges liés à cette catégorie
$sqlChallenges = $conn->prepare("SELECT challenges.*, users.username AS en_cours_par_nom FROM challenges LEFT JOIN users ON challenges.en_cours_par = users.id WHERE categorie_id = ?");
$sqlChallenges->bind_param("i", $categorieId);
$sqlChallenges->execute();
$challengesResult = $sqlChallenges->get_result();
$challenges = [];
while($row = $challengesResult->fetch_assoc()) {
    $challenges[] = $row;
}

function calculateElapsedTime($start, $end) {
    $startTime = strtotime($start);
    $endTime = strtotime($end);
    $diff = $endTime - $startTime;
    $hours = floor($diff / 3600);
    $mins = floor(($diff - ($hours * 3600)) / 60);
    $secs = floor($diff % 60);
    return sprintf("%dh %dm %ds", $hours, $mins, $secs);
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
    <title><?= htmlspecialchars($categorie["nom"]); ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        :root {--theme-color: <?= $themeColor; ?>;}
        body { background-color: #f4f4f4; }
        .navbar-custom { background-color: #007bff; }
        .navbar-custom .navbar-brand, .navbar-custom .nav-link { color: white; }
        .header-categorie { background: var(--theme-color); color: white; padding: 20px; border-radius: 8px; margin-top: 10px; margin-bottom: 30px; text-align: center; }
        .header-categorie h1 { margin-top: 0; }
        .challenge-card { background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,.1); }
        .challenge-title { color: black; font-weight: bold; margin-bottom: 10px; }
        .challenge-info { font-size: 14px; margin-bottom: 15px; color: #666; }
        .challenge-description { margin-bottom: 20px; }
        .challenge-actions a { margin-right: 10px; }
        .complet { background-color: #d4edda; }
        .en-cours { background-color: #fff3cd; }
        .back-link { font-weight: bold; color: var(--theme-color); display: inline-block; margin-top: 20px; }
        .back-link i { margin-right: 5px; }
        .footer { background-color: #007bff; color: white; padding: 20px 0; text-align: center; margin-top: 40px; }
        .timer-container { display: flex; align-items: center; justify-content: center; background-color: #e9ecef; border-radius: 5px; padding: 5px; margin-top: 10px; }
        .timer { font-weight: bold; color: #007bff; }
        body.night-mode { background-color: #333; color: white; }
        .navbar-custom.night-mode { background-color: #222; }
        .footer.night-mode { background-color: #222; }
        body.night-mode .card { background-color: #ffffff; color: black; }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container">
    <div class="header-categorie">
        <h1>Challenges dans la catégorie : <?= htmlspecialchars($categorie["nom"]); ?></h1>
    </div>

    <?php if(count($challenges) > 0): ?>
        <?php foreach($challenges as $challenge): ?>
            <div class="challenge-card <?= $challenge['est_complet'] ? 'complet' : ($challenge['en_cours_par'] == $_SESSION['id'] ? 'en-cours' : '') ?>">
                <h4 class="challenge-title"><?= htmlspecialchars($challenge["nom"]); ?></h4>
                <?php if ($challenge['est_complet']): ?>
                    <p class="challenge-info">Challenge flag par: <?= htmlspecialchars($challenge['en_cours_par_nom']); ?></p>
                    <?php
                    $elapsedTime = calculateElapsedTime($challenge['timer_start'], $challenge['timer_end']);
                    echo '<p class="challenge-info">Temps total pour flag: ' . $elapsedTime . '</p>';
                    ?>
                <?php else: ?>
                    <p class="challenge-info">Actuellement en cours par: <?= $challenge['en_cours_par_nom'] ? htmlspecialchars($challenge['en_cours_par_nom']) : "Personne"; ?></p>
                    <?php if ($challenge['timer_start'] != NULL): ?>
                        <div class="timer-container">
                            <span class="timer" data-start-time="<?= $challenge['timer_start']; ?>"></span>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                <p class="challenge-description"><?= nl2br(htmlspecialchars($challenge["description"])); ?></p>
                <div class="challenge-actions">
                    <a href="challenge.php?id=<?= $challenge["id"]; ?>" class="btn btn-primary">Voir les rapports</a>
                    <a href="writeup.php?challenge_id=<?= $challenge["id"]; ?>" class="btn btn-secondary">Write-up</a>
                    <?php if (!$challenge['est_complet']): ?>
                        <?php if ($challenge['en_cours_par'] == $_SESSION['id']): ?>
                            <a href="marquer_challenge.php?challenge_id=<?= $challenge['id']; ?>&action=retirer" class="btn btn-warning">Se désinscrire</a>
                        <?php else: ?>
                            <a href="marquer_challenge.php?challenge_id=<?= $challenge['id']; ?>&action=travailler" class="btn btn-info">Travailler sur ce challenge</a>
                        <?php endif; ?>
                        <a href="marquer_complet.php?challenge_id=<?= $challenge['id']; ?>&action=completer" class="btn btn-success">FLAG!</a>
                    <?php else: ?>
                        <a href="marquer_complet.php?challenge_id=<?= $challenge['id']; ?>&action=decompleter" class="btn btn-secondary">Unflag</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-info">Aucun challenge pour cette catégorie pour l'instant.</div>
    <?php endif; ?>

    <a href="event.php?id=<?= htmlspecialchars($eventId); ?>" class="back-link"><i class="fas fa-arrow-left"></i> Retour aux événements</a>
</div>
<?php include 'footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="js/themeSwitcher.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const timers = document.querySelectorAll('.timer');

    timers.forEach(timer => {
        let startTime = new Date(timer.getAttribute('data-start-time')).getTime();

        setInterval(() => {
            let now = Date.now();
            let elapsed = now - startTime;

            const hours = Math.floor((elapsed % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((elapsed % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((elapsed % (1000 * 60)) / 1000);

            timer.innerHTML = "Temps écoulé: " + hours + "h " + minutes + "m " + seconds + "s";
        }, 1000);
    });
});

</script>

</body>
</html>
