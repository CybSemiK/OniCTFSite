<?php
session_start();
require_once 'db.php';
date_default_timezone_set('Europe/Paris');
// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Vérifie l'existence de l'ID du challenge et de l'action
if (!isset($_GET['challenge_id']) || !isset($_GET['action'])) {
    die('Action non spécifiée ou ID du challenge manquant.');
}

$challengeId = $_GET['challenge_id'];
$action = $_GET['action'];
$userId = $_SESSION['id'];

$stmt = $conn->prepare("SELECT categorie_id FROM challenges WHERE id = ?");
$stmt->bind_param('i', $challengeId);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $categorieId = $row['categorie_id'];
} else {
    die('Challenge non trouvé.');
}

switch ($action) {
    case 'travailler':
        // Commencer ou reprendre le challenge
        $sql = "UPDATE challenges SET en_cours_par = ?, timer_start = IF(timer_start IS NULL, NOW(), timer_start), timer_resume = NOW() WHERE id = ?";
        break;
    case 'retirer':
        // Mettre en pause le challenge
        $sql = "UPDATE challenges SET en_cours_par = NULL, timer_pause = NOW() WHERE id = ? AND en_cours_par = ?";
        break;
    default:
        die('Action non valide.');
}

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Erreur de préparation de la requête.');
}
if ($action === 'travailler') {
    $stmt->bind_param('ii', $userId, $challengeId);
} else {
    $stmt->bind_param('ii', $challengeId, $userId);
}

if (!$stmt->execute()) {
    die('Erreur lors de l\'exécution de la requête.');
}

$stmt->close();

header('Location: categorie.php?categorie_id=' . $categorieId);
exit;
?>
