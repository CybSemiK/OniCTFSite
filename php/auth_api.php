<?php
// Activation de l'affichage des erreurs (à désactiver en production)
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

header('Content-Type: application/json');

// Connexion à la base de données
require 'db.php'; // Assurez-vous que ce fichier contient les informations de connexion à votre base de données

// Réponse par défaut
$response = ['success' => false, 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $password = "";
    $username_err = $password_err = "";

    // Validation de l'input
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter username.";
    } else {
        $username = trim($_POST["username"]);
    }

    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Vérification des identifiants
    if (empty($username_err) && empty($password_err)) {
        $sql = "SELECT id, username, password FROM users WHERE username = ?";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_username);
            $param_username = $username;

            // Exécution de la requête
            if ($stmt->execute()) {
                $stmt->store_result();

                if ($stmt->num_rows == 1) {
                    $stmt->bind_result($id, $username, $hashed_password);
                    if ($stmt->fetch()) {
                        if (password_verify($password, $hashed_password)) {
                            // Le mot de passe est correct, génération du token
                            $token = bin2hex(random_bytes(16)); // Générer un token sécurisé

                            // Mise à jour de la base de données avec le nouveau token pour cet utilisateur
                            $updateSql = "UPDATE users SET token = ? WHERE id = ?";
                            if ($updateStmt = $conn->prepare($updateSql)) {
                                $updateStmt->bind_param("si", $token, $id);
                                if ($updateStmt->execute()) {
                                    $response['success'] = true;
                                    $response['message'] = 'Authentication successful.';
                                    $response['token'] = $token;
                                } else {
                                    $response['message'] = 'Failed to update token.';
                                }
                                $updateStmt->close();
                            } else {
                                $response['message'] = 'Failed to prepare token update statement.';
                            }
                        } else {
                            // Le mot de passe n'est pas correct
                            $response['message'] = 'Invalid username or password.';
                        }
                    }
                } else {
                    $response['message'] = 'Invalid username or password.';
                }
            } else {
                $response['message'] = 'Query execution failed.';
            }
            $stmt->close();
        }
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
