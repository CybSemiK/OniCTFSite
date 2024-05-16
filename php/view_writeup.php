<?php
require_once 'db.php';

if (!isset($_GET["writeup_id"])) {
    die("ID du write-up non spécifié.");
}

$writeupId = $_GET["writeup_id"];
$stmt = $conn->prepare("SELECT writeup_path FROM writeups WHERE id = ?");
$stmt->bind_param("i", $writeupId);
$stmt->execute();
$result = $stmt->get_result();
if ($writeup = $result->fetch_assoc()) {
    $filePath = $writeup['writeup_path'];
    $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
    if (in_array($fileExtension, ['txt', 'md'])) {
        $content = file_get_contents($filePath);
        echo nl2br(htmlspecialchars($content));
    } else {
        die("Le fichier n'est pas un texte ou markdown.");
    }
} else {
    die("Write-up non trouvé.");
}
?>
