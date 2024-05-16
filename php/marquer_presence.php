<?php
session_start();
require 'db.php'; // Assurez-vous d'inclure votre script de connexion à la base de données ici

if (!isset($_SESSION['user_id'])) {
    // Rediriger l'utilisateur vers la page de connexion si non connecté
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $eventId = $_POST['event_id'];
    $userId = $_SESSION['user_id'];

    // Vérifier si l'utilisateur a déjà marqué sa présence
    $stmt = $conn->prepare("SELECT id FROM event_presences WHERE event_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $eventId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        // Insérer la présence
        $stmt = $conn->prepare("INSERT INTO event_presences (event_id, user_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $eventId, $userId);
        if ($stmt->execute()) {
            // Redirection avec un message de succès
            header('Location: event.php?id=' . $eventId . '&presence=success');
        } else {
            // Gérer l'erreur ici
        }
    } else {
        // L'utilisateur a déjà marqué sa présence
        header('Location: event.php?id=' . $eventId . '&already_marked=true');
    }
}
?>
