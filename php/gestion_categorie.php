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

function ajouterCategorie($nomEvent, $nomCategorie) {
    global $conn;
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

    $stmt = $conn->prepare("SELECT id FROM categories WHERE nom = ? AND event_id = ?");
    $stmt->bind_param("si", $nomCategorie, $eventId);
    $stmt->execute();
    $resultCategorie = $stmt->get_result();
    if ($resultCategorie->num_rows === 0) {
        $stmt = $conn->prepare("INSERT INTO categories (nom, event_id) VALUES (?, ?)");
        $stmt->bind_param("si", $nomCategorie, $eventId);
        if ($stmt->execute()) {
            echo "Catégorie ajoutée avec succès.";
        } else {
            echo "Erreur lors de l'ajout de la catégorie.";
        }
    } else {
        echo "La catégorie existe déjà pour cet événement.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    if (verifierToken($token)) {
        $nomEvent = $_POST['event'] ?? '';
        $nomCategorie = $_POST['categorie'] ?? '';
        ajouterCategorie($nomEvent, $nomCategorie);
    } else {
        echo "Accès refusé : Token invalide.";
    }
} else {
    echo "Méthode HTTP non supportée.";
}
?>
