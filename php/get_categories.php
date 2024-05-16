<?php
require_once "db.php";

if (isset($_GET['eventId']) && !empty($_GET['eventId'])) {
    $eventId = $_GET['eventId'];

    $stmt = $conn->prepare("SELECT id, nom FROM categories WHERE event_id = ?");
    $stmt->bind_param("i", $eventId);
    $stmt->execute();
    $result = $stmt->get_result();
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    echo json_encode($categories);
} else {
    echo json_encode([]);
}
?>
