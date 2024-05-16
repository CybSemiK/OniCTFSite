<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['username'])) {
    $_SESSION['error'] = "Vous devez être connecté pour effectuer cette action.";
    header("Location: login.php"); // Rediriger vers la page de connexion
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["writeup_file"])) {
    if ($_FILES["writeup_file"]["error"] != 0) {
        $_SESSION['error'] = "Erreur lors de l'upload du fichier: " . $_FILES["writeup_file"]["error"];
        header("Location: writeup.php?challenge_id=" . $_POST['challenge_id']);
        exit;
    }

    $challengeId = $_POST['challenge_id'];
    $userId = $_SESSION['user_id'];
    $username = $_SESSION['username'];

    // Récupérer le nom du challenge
    $stmt = $conn->prepare("SELECT nom FROM challenges WHERE id = ?");
    $stmt->bind_param("i", $challengeId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($challenge = $result->fetch_assoc()) {
        $challengeName = preg_replace('/[^A-Za-z0-9-]/', '', str_replace(' ', '-', $challenge['nom']));
    } else {
        $_SESSION['error'] = "Challenge non trouvé.";
        header("Location: writeup.php?challenge_id=" . $challengeId);
        exit;
    }

    $file = $_FILES['writeup_file'];
    $fileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $allowedTypes = ['txt', 'pdf', 'md'];

    if (!in_array($fileType, $allowedTypes)) {
        $_SESSION['error'] = "Type de fichier non autorisé. Les fichiers autorisés sont .txt, .pdf, .md.";
        header("Location: writeup.php?challenge_id=" . $challengeId);
        exit;
    }

    // Construction du nom de fichier
    $baseFileName = "{$challengeName}-writeup-{$username}";
    $i = 1;
    $newFileName = $baseFileName . "-" . $i . "." . $fileType;
    $targetDir = "uploads/writeups/";
    while (file_exists($targetDir . $newFileName)) {
        $i++;
        $newFileName = $baseFileName . "-" . $i . "." . $fileType;
    }
    $targetFilePath = $targetDir . $newFileName;

    // Déplacement du fichier et insertion dans la base de données
    if (move_uploaded_file($file["tmp_name"], $targetFilePath)) {
        $stmt = $conn->prepare("INSERT INTO writeups (challenge_id, writeup_path, user_id) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $challengeId, $targetFilePath, $userId);
        $stmt->execute();
        header("Location: writeup.php?challenge_id=" . $challengeId);
        exit;
    } else {
        $_SESSION['error'] = "Erreur lors du déplacement du fichier.";
        header("Location: writeup.php?challenge_id=" . $challengeId);
        exit;
    }
} else {
    $_SESSION['error'] = "Aucun fichier uploadé ou erreur lors de l'upload.";
    header("Location: writeup.php"); // Assurez-vous que cette redirection est appropriée pour votre structure de site
    exit;
}
?>
