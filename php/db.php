<?php
$db_host = 'localhost';
$db_username = 'changemeuser'; // Remplacez par votre nom d'utilisateur MySQL
$db_password = 'changemepassword'; // Remplacez par votre mot de passe MySQL
$db_name = 'changemedb';

$conn = new mysqli($db_host, $db_username, $db_password, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
