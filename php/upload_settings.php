<?php
session_start();
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $currentSettingsQuery = $conn->query("SELECT logo_path, theme_color, team_name FROM settings ORDER BY id DESC LIMIT 1");
    $currentSettings = $currentSettingsQuery->fetch_assoc();

    $logoPath = $currentSettings['logo_path'] ?? 'path/to/default/logo.png';
    $themeColor = $_POST['theme_color'] ?? $currentSettings['theme_color'] ?? '#007bff';
    $teamName = $_POST['team_name'] ?? $currentSettings['team_name'] ?? 'Nom de votre équipe';

    if (isset($_FILES["logo"]) && $_FILES["logo"]["error"] == 0) {
        $targetDirectory = "uploads/";
        $fileName = time() . '_' . basename($_FILES["logo"]["name"]);
        $targetFilePath = $targetDirectory . $fileName;
        if (getimagesize($_FILES["logo"]["tmp_name"])) {
            if (move_uploaded_file($_FILES["logo"]["tmp_name"], $targetFilePath)) {
                $logoPath = $targetFilePath;
            }
        }
    }

    $stmt = $conn->prepare("INSERT INTO settings (logo_path, theme_color, team_name) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE logo_path = VALUES(logo_path), theme_color = VALUES(theme_color), team_name = VALUES(team_name)");
    $stmt->bind_param("sss", $logoPath, $themeColor, $teamName);
    $stmt->execute();

    header("Location: admin_panel.php");
    exit;
} else {
    header("Location: login.php");
    exit;
}
?>
