<?php
require_once 'db.php';

if (!isset($_GET['rapport_id'])) {
    echo "<script>alert('ID du rapport non spécifié.'); window.history.back();</script>"; // Retour à la page précédente si l'ID n'est pas spécifié
    exit;
}

$rapportId = $_GET['rapport_id'];

// Supposons que vous devez récupérer l'ID du challenge associé au rapport pour la redirection
$stmt = $conn->prepare("SELECT fichier, challenge_id FROM rapports WHERE id = ?");
$stmt->bind_param("i", $rapportId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('Rapport non trouvé.'); window.history.back();</script>"; // Retour à la page précédente si le rapport n'est pas trouvé
    exit;
}

$rapport = $result->fetch_assoc();
$fichier = $rapport['fichier'];
$challengeId = $rapport['challenge_id']; // ID du challenge pour la redirection

// Suppression du fichier rapport
if (file_exists($fichier)) {
    unlink($fichier);
}

// Suppression de l'entrée de la base de données
$stmt = $conn->prepare("DELETE FROM rapports WHERE id = ?");
$stmt->bind_param("i", $rapportId);
$stmt->execute();

// Redirection vers la page du challenge correspondant
header("Location: challenge.php?id=" . $challengeId); // Assurez-vous que le chemin et les paramètres sont corrects
exit;
?>
