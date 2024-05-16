<?php
require_once 'db.php';

header('Content-Type: application/json');
session_start();


// Fonction pour récupérer le token d'authentification depuis les en-têtes de la requête
function getTokenFromHeaders() {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        list(, $token) = explode(' ', $headers['Authorization'], 2);
        return $token;
    }
    return null;
}

// Fonction pour vérifier le token et retourner l'ID de l'utilisateur correspondant
function verifyToken($token) {
    global $conn;
    $stmt = $conn->prepare("SELECT id FROM users WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        return $user['id'];
    }
    return false;
}

$token = getTokenFromHeaders();
$userId = verifyToken($token);
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Token non valide ou manquant.']);
    exit;
}

$event = $_POST['event'] ?? '';
$categorie = $_POST['categorie'] ?? '';
$challenge = $_POST['challenge'] ?? '';
$rapport = $_FILES['rapport'] ?? null;

if (!$event || !$categorie || !$challenge || !$rapport) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes ou incorrectes.']);
    exit;
}

$challengeName = str_replace(' ', '_', $challenge);
$originalFileName = pathinfo($rapport['name'], PATHINFO_FILENAME);
$fileExtension = pathinfo($rapport['name'], PATHINFO_EXTENSION);
$newFileName = $challengeName . "-" . $originalFileName . "." . $fileExtension;

$uploadDir = 'uploads/';
$uploadPath = $uploadDir . basename($newFileName);

if (move_uploaded_file($rapport['tmp_name'], $uploadPath)) {
    $stmt = $conn->prepare("SELECT id FROM challenges WHERE nom = ? AND categorie_id = (SELECT id FROM categories WHERE nom = ?)");
    $stmt->bind_param("ss", $challenge, $categorie);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Challenge non trouvé.']);
        exit;
    }
    $challengeData = $result->fetch_assoc();
    $challengeId = $challengeData['id'];

    $stmt = $conn->prepare("INSERT INTO rapports (challenge_id, user_id, fichier) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $challengeId, $userId, $uploadPath);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'insertion du rapport.']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Rapport uploadé avec succès.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'upload du rapport.']);
}
?>
