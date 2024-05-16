<?php
require_once 'db.php';

function verifierToken($token) {
    global $conn;
    $stmt = $conn->prepare("SELECT id FROM users WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows === 1;
}

function ajouterChallenge($nomEvent, $nomCategorie, $nomChallenge) {
    global $conn;
    // Récupération de l'ID de l'événement
    $stmt = $conn->prepare("SELECT id FROM ctf_events WHERE nom = ?");
    $stmt->bind_param("s", $nomEvent);
    $stmt->execute();
    $resultEvent = $stmt->get_result();
    if ($resultEvent->num_rows === 0) {
        echo "Événement non trouvé.";
        return;
    }
    $event = $resultEvent->fetch_assoc();
    $eventId = $event['id'];

    // Récupération de l'ID de la catégorie
    $stmt = $conn->prepare("SELECT id FROM categories WHERE nom = ? AND event_id = ?");
    $stmt->bind_param("si", $nomCategorie, $eventId);
    $stmt->execute();
    $resultCategorie = $stmt->get_result();
    if ($resultCategorie->num_rows === 0) {
        echo "Catégorie non trouvée.";
        return;
    }
    $categorie = $resultCategorie->fetch_assoc();
    $categorieId = $categorie['id'];

    // Vérification de l'existence du challenge
    $stmt = $conn->prepare("SELECT id FROM challenges WHERE nom = ? AND categorie_id = ?");
    $stmt->bind_param("si", $nomChallenge, $categorieId);
    $stmt->execute();
    $resultChallenge = $stmt->get_result();
    if ($resultChallenge->num_rows === 0) {
        // Insertion du challenge
        $stmt = $conn->prepare("INSERT INTO challenges (nom, categorie_id) VALUES (?, ?)");
        $stmt->bind_param("si", $nomChallenge, $categorieId);
        if ($stmt->execute()) {
            echo "Challenge ajouté avec succès.";
        } else {
            echo "Erreur lors de l'ajout du challenge.";
        }
    } else {
        echo "Le challenge existe déjà pour cette catégorie.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    if (verifierToken($token)) {
        $nomEvent = $_POST['event'] ?? '';
        $nomCategorie = $_POST['categorie'] ?? '';
        $nomChallenge = $_POST['challenge'] ?? '';
        ajouterChallenge($nomEvent, $nomCategorie, $nomChallenge);
    } else {
        echo "Accès refusé : Token invalide.";
    }
} else {
    echo "Méthode HTTP non supportée.";
}
?>
