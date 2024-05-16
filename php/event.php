<?php
session_start();
require_once "db.php";

// Vérification de l'ID de l'événement passé en paramètre
if (!isset($_GET["id"])) {
    header("Location: ctf.php");
    exit;
}

$eventId = $_GET["id"];

// Récupération des informations de l'événement
$sqlEvent = $conn->prepare("SELECT * FROM ctf_events WHERE id = ?");
$sqlEvent->bind_param("i", $eventId);
$sqlEvent->execute();
$eventResult = $sqlEvent->get_result();
$event = $eventResult->fetch_assoc();

if (!$event) {
    echo "Événement non trouvé.";
    exit;
}

$alreadyMarked = false;
$userId = $_SESSION['loggedin'] ? $_SESSION['id'] : null;

// Vérifiez si l'utilisateur a déjà marqué sa présence
if ($userId) {
    $checkPresenceQuery = "SELECT * FROM event_presences WHERE user_id = ? AND event_id = ?";
    $checkPresenceStmt = $conn->prepare($checkPresenceQuery);
    $checkPresenceStmt->bind_param("ii", $userId, $eventId);
    $checkPresenceStmt->execute();
    $result = $checkPresenceStmt->get_result();
    $alreadyMarked = $result->num_rows > 0;
}

// Traitement du marquage ou du retrait de la présence
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'markPresence' && !$alreadyMarked) {
        $markPresenceQuery = "INSERT INTO event_presences (user_id, event_id) VALUES (?, ?)";
        $markPresenceStmt = $conn->prepare($markPresenceQuery);
        $markPresenceStmt->bind_param("ii", $userId, $eventId);
        $markPresenceStmt->execute();
    } elseif (isset($_POST['action']) && $_POST['action'] == 'removePresence' && $alreadyMarked) {
        $removePresenceQuery = "DELETE FROM event_presences WHERE user_id = ? AND event_id = ?";
        $removePresenceStmt = $conn->prepare($removePresenceQuery);
        $removePresenceStmt->bind_param("ii", $userId, $eventId);
        $removePresenceStmt->execute();
    }
    // Rafraîchissement pour montrer la mise à jour
    header("Location: event.php?id=$eventId");
    exit;
}

// Récupération de la liste des participants
$sqlParticipants = $conn->prepare("SELECT username FROM users JOIN event_presences ON users.id = event_presences.user_id WHERE event_presences.event_id = ?");
$sqlParticipants->bind_param("i", $eventId);
$sqlParticipants->execute();
$participantsResult = $sqlParticipants->get_result();
$participants = [];
while($row = $participantsResult->fetch_assoc()) {
    $participants[] = $row['username'];
}

// Gestion du bouton de présence
if(isset($_POST['markPresence']) && isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    $userId = $_SESSION['id'];
    // Vérifiez si l'utilisateur a déjà marqué sa présence
    $checkPresence = $conn->prepare("SELECT * FROM event_presences WHERE user_id = ? AND event_id = ?");
    $checkPresence->bind_param("ii", $userId, $eventId);
    $checkPresence->execute();
    if($checkPresence->get_result()->num_rows == 0) {
        $markPresence = $conn->prepare("INSERT INTO event_presences (user_id, event_id) VALUES (?, ?)");
        $markPresence->bind_param("ii", $userId, $eventId);
        $markPresence->execute();
        // Rafraîchissement pour montrer la mise à jour
        header("Location: event.php?id=$eventId");
        exit;
    }
}

// Récupération des catégories liées à cet événement
$sqlCategories = $conn->prepare("SELECT id, nom FROM categories WHERE event_id = ?");
$sqlCategories->bind_param("i", $eventId);
$sqlCategories->execute();
$categoriesResult = $sqlCategories->get_result();
$categories = [];
while ($row = $categoriesResult->fetch_assoc()) {
    $categories[] = $row;
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($event["nom"]); ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        :root {--theme-color: <?= $themeColor; ?>;}
        body { background-color: #f4f4f4; }
        .navbar-custom { background-color: #007bff; }
        .navbar-custom .navbar-brand, .navbar-custom .nav-link { color: white; }
        .event-header { background: var(--theme-color); color: white; padding: 20px; border-radius: 8px; margin-top: 10px; margin-bottom: 30px; text-align: center; }
        .event-header h1 { margin-top: 10px; }
        .event-info { margin-top: 0px; }
        .event-info p { font-size: 16px; }
        .icon-text { display: flex; align-items: center; }
        .icon-text i { margin-right: 8px; }
        .participants-list, .categories-list { background: white; padding: 20px; border-radius: 8px; margin-top: 20px; box-shadow: 0 4px 6px rgba(0,0,0,.1); }
        .list-title { font-weight: bold; margin-bottom: 20px; }
        ul { padding-left: 20px; }
        li { margin-bottom: 10px; }
        .back-link { font-weight: bold; color: var(--theme-color); display: inline-block; margin-top: 20px; }
        .back-link i { margin-right: 5px; }
        .footer { background-color: #007bff; color: white; padding: 20px 0; text-align: center; margin-top: 40px; }
        body.night-mode { background-color: #333; color: white; }
        .navbar-custom.night-mode { background-color: #222; }
        .footer.night-mode { background-color: #222; }
        body.night-mode .card, .participants-list, .categories-list { background-color: #ffffff; color: black; }
</style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container">
    <!-- Event Header -->
    <div class="event-header">
        <h1><?= htmlspecialchars($event["nom"]); ?></h1>
    </div>

    <!-- Event Information -->
    <div class="event-info">
        <p class="icon-text"><i class="fas fa-calendar-alt"></i>Date de début : <?= htmlspecialchars($event["dateDebut"]); ?></p>
        <p class="icon-text"><i class="fas fa-calendar-check"></i>Date de fin : <?= htmlspecialchars($event["dateFin"]); ?></p>
        <p><?= nl2br(htmlspecialchars($event["description"])); ?></p>
        <?php if ($userId): ?>
            <?php if (!$alreadyMarked): ?>
                <form method="post">
                    <input type="hidden" name="action" value="markPresence">
                    <button type="submit" class="btn btn-success">Marquer ma participation</button>
                </form>
            <?php else: ?>
                <form method="post">
                    <input type="hidden" name="action" value="removePresence">
                    <button type="submit" class="btn btn-warning">Retirer ma participation</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Participants List -->
    <div class="participants-list">
        <h3 class="list-title">Participants</h3>
        <?php if(count($participants) > 0): ?>
            <ul>
                <?php foreach($participants as $participant): ?>
                    <li><?= htmlspecialchars($participant); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Aucun participant pour l'instant.</p>
        <?php endif; ?>
    </div>

    <!-- Categories List -->
    <div class="categories-list">
        <h3 class="list-title">Catégories</h3>
        <div class="row">
            <?php if(count($categories) > 0): ?>
                <?php foreach($categories as $categorie): ?>
                    <div class="col-md-4">
                        <div class="card card-category mb-4">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($categorie['nom']); ?></h5>
                                <a href="categorie.php?categorie_id=<?= $categorie['id']; ?>" class="btn btn-primary">Explorer</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Aucune catégorie pour cet événement.</p>
            <?php endif; ?>
        </div>
    </div>

    <a href="ctf.php" class="back-link"><i class="fas fa-arrow-left"></i>Retour aux événements</a>
</div>
<?php include 'footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="js/themeSwitcher.js"></script>
</body>
</html>
