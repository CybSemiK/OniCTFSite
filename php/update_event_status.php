<?php
require_once 'db.php'; // Assurez-vous d'avoir votre connexion à la base de données ici

// Récupération de la date actuelle
$dateNow = date("Y-m-d");

// Mise à jour des événements prévisionnels en cours
$sqlUpdateToOngoing = "UPDATE ctf_events SET statut = 'en cours' WHERE dateDebut <= ? AND (dateFin >= ? OR dateFin IS NULL) AND statut = 'prévisionnel'";
$stmt = $conn->prepare($sqlUpdateToOngoing);
$stmt->bind_param("ss", $dateNow, $dateNow);
$stmt->execute();

// Mise à jour des événements en cours en passés
$sqlUpdateToPast = "UPDATE ctf_events SET statut = 'passé' WHERE dateFin < ? AND statut = 'en cours'";
$stmt = $conn->prepare($sqlUpdateToPast);
$stmt->bind_param("s", $dateNow);
$stmt->execute();

echo "Mise à jour des statuts des événements effectuée.";
?>
