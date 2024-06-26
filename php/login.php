<?php
// Démarrer la session
session_start();

// Inclure le fichier de configuration de la base de données
require_once "db.php";

// Définir les variables et initialiser avec des valeurs vides
$username = $password = "";
$username_err = $password_err = $login_err = "";

// Traitement des données du formulaire après soumission
if($_SERVER["REQUEST_METHOD"] == "POST"){

    // Vérifiez si le nom d'utilisateur est vide
    if(empty(trim($_POST["username"]))){
        $username_err = "Veuillez entrer votre nom d'utilisateur.";
    } else{
        $username = trim($_POST["username"]);
    }

    // Vérifiez si le mot de passe est vide
    if(empty(trim($_POST["password"]))){
        $password_err = "Veuillez entrer votre mot de passe.";
    } else{
        $password = trim($_POST["password"]);
    }

    // Valider les identifiants
    if(empty($username_err) && empty($password_err)){
        // Préparer une requête SELECT pour récupérer également le rôle
        $sql = "SELECT id, username, password, role FROM users WHERE username = ?";

        if($stmt = $conn->prepare($sql)){
            // Lier les variables à l'instruction préparée comme paramètres
            $stmt->bind_param("s", $param_username);

            // Définir le paramètre
            $param_username = $username;

            // Exécuter la requête
            if($stmt->execute()){
                // Stocker le résultat
                $stmt->store_result();

                // Vérifier si le nom d'utilisateur existe, si oui, vérifier le mot de passe
                if($stmt->num_rows == 1){
                    // Lier les variables de résultat
                    $stmt->bind_result($id, $username, $hashed_password, $role);
                    if($stmt->fetch()){
                        if(password_verify($password, $hashed_password)){
                            // Mot de passe correct, démarrer une nouvelle session

                            // Stocker les données dans les variables de session
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["role"] = $role;

                            // Rediriger l'utilisateur selon son rôle
                            if($_SESSION["role"] === 'admin'){
                                header("location: admin_panel.php");
                                exit;
                            } else {
                                header("location: ctf.php");
                                exit;
                            }
                        } else{
                            // Afficher un message d'erreur si le mot de passe n'est pas valide
                            $login_err = "Nom d'utilisateur ou mot de passe invalide.";
                        }
                    }
                } else{
                    // Afficher un message d'erreur si le nom d'utilisateur n'existe pas
                    $login_err = "Nom d'utilisateur ou mot de passe invalide.";
                }
            } else{
                echo "Oops! Quelque chose s'est mal passé. Veuillez réessayer plus tard.";
            }

            // Fermer la déclaration
            $stmt->close();
        }
    }

    // Fermer la connexion
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <style>
        body{ font: 14px sans-serif; }
        .wrapper{ width: 360px; padding: 20px; margin: auto; background-color: #f2f2f2; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .form-group{ margin-bottom: 20px; }
        .form-group label{ display: block; }
        .form-group input[type="text"],
        .form-group input[type="password"]{ width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .btn{ background-color: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .btn:hover{ background-color: #45a049; }
        .error{ color: red; }
    </style>
</head>
<body>
    <div class="wrapper">
        <h2>Connexion</h2>
        <p>Veuillez remplir vos identifiants pour vous connecter.</p>
        <?php
        if(!empty($login_err)){
            echo '<div class="error">' . $login_err . '</div>';
        }
        ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Nom d'utilisateur</label>
                <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>">
                <span class="error"><?php echo $username_err; ?></span>
            </div>
            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="password" class="form-control">
                <span class="error"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn" value="Connexion">
            </div>
        </form>
    </div>
</body>
</html>



