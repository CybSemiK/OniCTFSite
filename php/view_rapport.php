<?php
require_once 'db.php';

// Fonction pour obtenir le chemin du fichier rapport basé sur son ID
function getRapportPathById($rapportId) {
    global $conn;
    $stmt = $conn->prepare("SELECT fichier FROM rapports WHERE id = ?");
    $stmt->bind_param("i", $rapportId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['fichier'];
    } else {
        return false;
    }
}

$rapportId = isset($_GET['rapport_id']) ? intval($_GET['rapport_id']) : 0;
$action = isset($_GET['action']) ? $_GET['action'] : 'view';

if ($rapportId <= 0) {
    echo "ID du rapport non spécifié ou invalide.";
    exit;
}

$fichier = getRapportPathById($rapportId);

if (!$fichier || !file_exists($fichier)) {
    echo "Fichier du rapport non trouvé.";
    exit;
}

if ($action == 'download') {
    // Force le téléchargement pour n'importe quel type de fichier
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($fichier) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($fichier));
    readfile($fichier);
    exit;
}

if ($action == 'view') {
    // Pour la visualisation, traitez différemment selon le type de fichier
    $fileExtension = pathinfo($fichier, PATHINFO_EXTENSION);
    if ($fileExtension == 'txt') {
        // Pour les fichiers .txt, affichez le contenu dans le navigateur
        header('Content-Type: text/plain');
        readfile($fichier);
    } elseif ($fileExtension == 'xml') {
        // Pour les fichiers .xml, définissez l'en-tête de contenu approprié
        header('Content-Type: application/xml');
        readfile($fichier);
    } elseif (in_array($fileExtension, array('jpg', 'jpeg', 'png', 'gif'))) {
        // Pour les images, définissez l'en-tête de contenu approprié
        $mime = mime_content_type($fichier);
        header('Content-Type: ' . $mime);
        readfile($fichier);
    } else {
        // Pour les autres types, forcez le téléchargement
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($fichier) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($fichier));
        readfile($fichier);
    }
    exit;
}
?>
