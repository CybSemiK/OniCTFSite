<?php
require_once 'db.php';

if (!isset($_GET["writeup_id"])) {
    die("ID du write-up non spécifié.");
}

if (!isset($_GET["challenge_id"])) {
    die("ID du challenge non spécifié.");
}

$writeupId = $_GET["writeup_id"];
$challengeId = $_GET["challenge_id"]; // Récupérer l'ID du challenge directement depuis la requête GET

$stmt = $conn->prepare("SELECT writeup_path FROM writeups WHERE id = ?");
$stmt->bind_param("i", $writeupId);
$stmt->execute();
$result = $stmt->get_result();

if ($writeup = $result->fetch_assoc()) {
    $filePath = $writeup['writeup_path'];

    if (file_exists($filePath)) {
        unlink($filePath);  // Supprimer le fichier du serveur
    }

    $stmt = $conn->prepare("DELETE FROM writeups WHERE id = ?");
    $stmt->bind_param("i", $writeupId);
    $stmt->execute();

    // Redirection vers la page des write-ups avec l'ID du challenge
    header("Location: writeup.php?challenge_id=" . $challengeId);
    exit;
} else {
    die("Write-up non trouvé.");
}
?>
