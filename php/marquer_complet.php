<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["id"])) {
    die("Vous devez être connecté pour effectuer cette action.");
}

if (!isset($_GET["challenge_id"]) || !isset($_GET["action"])) {
    die("Action non spécifiée ou ID du challenge manquant.");
}

$challengeId = $_GET["challenge_id"];
$action = $_GET["action"];
$userId = $_SESSION["id"];

$stmt = $conn->prepare("SELECT categorie_id FROM challenges WHERE id = ?");
$stmt->bind_param("i", $challengeId);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $categorieId = $row["categorie_id"];
} else {
    die("Challenge non trouvé.");
}

if ($action === "completer") {
    $sql = "UPDATE challenges SET est_complet = 1, en_cours_par = ?, completed_at = NOW(), timer_end = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Erreur lors de la préparation de la mise à jour : " . $conn->error);
    }
    $stmt->bind_param("ii", $userId, $challengeId);
} elseif ($action === "decompleter") {
    $sql = "UPDATE challenges SET est_complet = 0, en_cours_par = NULL, completed_at = NULL, timer_end = NULL WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Erreur lors de la préparation de la mise à jour : " . $conn->error);
    }
    $stmt->bind_param("i", $challengeId);
}

if ($stmt->execute() === false) {
    die("Erreur lors de l'exécution de la mise à jour : " . $stmt->error);
}

$stmt->close();
header("Location: categorie.php?categorie_id=" . $categorieId);
exit;
?>
