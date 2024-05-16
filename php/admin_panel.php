<?php
session_start();
require_once "db.php";
require_once "navbar.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: login.php");
    exit;
}

$sql = "SELECT id, username FROM users";
$result = $conn->query($sql);

$users = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Récupération des événements existants
$sqlEvents = "SELECT id, nom FROM ctf_events";
$resultEvents = $conn->query($sqlEvents);

$events = [];
if ($resultEvents->num_rows > 0) {
    while ($row = $resultEvents->fetch_assoc()) {
        $events[] = $row;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] == "createUser") {
        $username = trim($_POST["username"]);
        $password = trim($_POST["password"]);
        $email = trim($_POST["email"]);
        $role = trim($_POST["role"]);

        $sql = "INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt->bind_param("ssss", $username, $hashed_password, $email, $role);

            if ($stmt->execute()) {
                echo "<script>alert('Utilisateur ajouté avec succès.');</script>";
            } else {
                echo "<script>alert('Erreur lors de l'ajout de l'utilisateur.');</script>";
            }
            $stmt->close();
        }
    } elseif (isset($_POST['action']) && $_POST['action'] == "deleteUser") {
        $usernameToDelete = $_POST['usernameToDelete'];

        $conn->query("SET FOREIGN_KEY_CHECKS = 0");
        $sql = "DELETE FROM users WHERE username = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $usernameToDelete);

            if ($stmt->execute()) {
                echo "<script>alert('Utilisateur supprimé avec succès.');</script>";
            } else {
                echo "<script>alert('Erreur lors de la suppression de l'utilisateur.');</script>";
            }
            $stmt->close();
        } $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    } elseif (isset($_POST['action']) && $_POST['action'] == "modifyUserRole") {
        $userIdToModify = $_POST['userIdToModify'];
        $newRole = $_POST['newRole'];

        $sql = "UPDATE users SET role = ? WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("si", $newRole, $userIdToModify);

            if ($stmt->execute()) {
                echo "<script>alert('Rôle de l'utilisateur modifié avec succès.');</script>";
            } else {
                echo "<script>alert('Erreur lors de la modification du rôle.');</script>";
            }
            $stmt->close();
        }
    } elseif (isset($_POST['action']) && $_POST['action'] == "createEvent") {
        $eventName = trim($_POST["eventName"]);
        $eventDescription = trim($_POST["eventDescription"]);
        $startDate = trim($_POST["startDate"]);
        $endDate = trim($_POST["endDate"]) ? trim($_POST["endDate"]) : null;
        $statut = trim($_POST["statut"]);

        $sql = "INSERT INTO ctf_events (nom, description, statut, dateDebut, dateFin) VALUES (?, ?, ?, ?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssss", $eventName, $eventDescription, $statut, $startDate, $endDate);

            if ($stmt->execute()) {
                echo "<script>alert('Événement créé avec succès.');</script>";
            } else {
                echo "<script>alert('Erreur lors de la création de l'événement.');</script>";
            }
            $stmt->close();
        }
    } elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == "updateEventStatus") {
        $eventId = $_POST['eventId'];
        $newStatus = $_POST['newStatus'];

        // Préparation de la requête pour mettre à jour le statut de l'événement
        $sql = "UPDATE ctf_events SET statut = ? WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("si", $newStatus, $eventId);

            if ($stmt->execute()) {
                echo "<script>alert('Le statut de l'événement a été mis à jour avec succès.');</script>";
            } else {
                echo "<script>alert('Erreur lors de la mise à jour du statut de l'événement.');</script>";
            }
            $stmt->close();
         }
    } elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == "deleteCategory") {
        // Récupère l'ID de la catégorie à supprimer et l'ID de l'événement pour vérification supplémentaire
        $categoryIdToDelete = $_POST['categoryIdToDelete'];
        $eventId = $_POST['eventId'];

        // Étape 1 : Récupérer les chemins des fichiers de rapport
        $reportPathsStmt = $conn->prepare("
            SELECT rapports.fichier FROM rapports
            JOIN challenges ON rapports.challenge_id = challenges.id
            WHERE challenges.categorie_id = ?
        ");
        $reportPathsStmt->bind_param("i", $categoryIdToDelete);
        $reportPathsStmt->execute();
        $reportPathsResult = $reportPathsStmt->get_result();

        while ($reportPath = $reportPathsResult->fetch_assoc()) {
            // Étape 2 : Supprimer les fichiers du serveur
            if (file_exists($reportPath['fichier'])) {
                unlink($reportPath['fichier']);
            }
        }

        // Vérifier d'abord si la catégorie appartient bien à l'événement spécifié
        $stmt = $conn->prepare("SELECT id FROM categories WHERE id = ? AND event_id = ?");
        $stmt->bind_param("ii", $categoryIdToDelete, $eventId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            // La catégorie existe et appartient à l'événement, procéder à la suppression
            $deleteStmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
            $deleteStmt->bind_param("i", $categoryIdToDelete);

            if ($deleteStmt->execute()) {
                echo "<script>alert('Catégorie supprimée avec succès.');</script>";
            } else {
                echo "<script>alert('Erreur lors de la suppression de la catégorie.');</script>";
            }

            $deleteStmt->close();
        }
    } elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == "deleteEvent") {
        $eventIdToDelete = $_POST['eventIdToDelete'];
        $filesSql = "SELECT fichier FROM rapports WHERE challenge_id IN (
            SELECT id FROM challenges WHERE categorie_id IN (
                SELECT id FROM categories WHERE event_id = ?
            )
        )";

        // Préparation de la requête de suppression
        $stmt = $conn->prepare($filesSql);
    $stmt->bind_param("i", $eventIdToDelete);
    $stmt->execute();
    $filesResult = $stmt->get_result();
    while ($file = $filesResult->fetch_assoc()) {
        if (file_exists($file['fichier'])) {
            unlink($file['fichier']); // Supprime le fichier du serveur
        }
    }
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    $conn->query("DELETE FROM rapports WHERE challenge_id IN (SELECT id FROM challenges WHERE categorie_id IN (SELECT id FROM categories WHERE event_id = $eventIdToDelete))");
    $conn->query("DELETE FROM challenges WHERE categorie_id IN (SELECT id FROM categories WHERE event_id = $eventIdToDelete)");
    $conn->query("DELETE FROM categories WHERE event_id = $eventIdToDelete");

    // Suppression de l'événement
    $deleteEventSql = "DELETE FROM ctf_events WHERE id = ?";
    $deleteStmt = $conn->prepare($deleteEventSql);
    $deleteStmt->bind_param("i", $eventIdToDelete);
    if ($deleteStmt->execute()) {
        echo "<script>alert('Événement et toutes les données associées ont été supprimées avec succès.');</script>";
    } else {
        echo "<script>alert('Erreur lors de la suppression de l'événement.');</script>";
    }
    $deleteStmt->close();
    }$conn->query("SET FOREIGN_KEY_CHECKS = 1");
    // Rafraîchir la page pour montrer les changements
    echo "<script>window.location = 'admin_panel.php';</script>";
}
$query = "SELECT theme_color FROM settings ORDER BY id DESC LIMIT 1";
$result = $conn->query($query);
$settings = $result->fetch_assoc();

$themeColor = $settings['theme_color'] ?? '#007bff';

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panneau d'Administration</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        :root {--theme-color: <?= $themeColor; ?>;}
        body {
            background-color: #f8f9fa;
        }
        .dashboard-header {
            background: var(--theme-color);
            color: #fff;
            padding: 20px 0;
            border-radius: 0.25rem;
            margin: 10px -20px 20px -20px;
            text-align: center;
        }
        .modal-header {
            background: #0062cc;
            color: #fff;
        }
        .btn-ctf {
            margin-right: 20px;
            color: white;
        }
        .btn-logout {
            background-color: #dc3545;
            color: #fff;
            border: none;
        }
        .btn-logout:hover {
            background-color: #c82333;
            color: #fff;
        }
        .dashboard-actions {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }
        .list-group-item-action {
            transition: background-color .15s ease-in-out, color .15s ease-in-out;
        }
        .list-group-item-action:hover, .list-group-item-action:focus {
            color: #007bff;
            background-color: #f8f9fa;
        }
        .footer {
            background-color: #007bff;
            color: white;
            padding: 20px 0;
            text-align: center;
            margin-top: 70px;
        }
        .navbar-custom { background-color: #007bff; }
        .navbar-custom .navbar-brand, .navbar-custom .nav-link { color: white; }
         body.night-mode { background-color: #333; color: white; }
        .navbar-custom.night-mode { background-color: #222; }
        .footer.night-mode { background-color: #222; }
        body.night-mode .card { background-color: #ffffff; color: black; }
       .btn-multicolor {
        background: linear-gradient(45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
        background-size: 400% 400%;
        animation: gradientBG 15s ease infinite;
        border: none;
        color: white;
        }

        @keyframes gradientBG {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
        }
    </style>
</head>
<body>


<div class="container">
    <div class="dashboard-header">
        <h2>Panneau d'Administration</h2>
    </div>

<div class="list-group">
        <a href="settings.php" class="btn btn-primary btn-multicolor">Personnaliser l'apparence</a><br>
        <a href="#" class="list-group-item list-group-item-action" data-toggle="modal" data-target="#createUserModal">Créer un nouvel utilisateur</a>
        <a href="#" class="list-group-item list-group-item-action" data-toggle="modal" data-target="#modifyUserRoleModal">Modifier le rôle d'un utilisateur</a>
        <a  href="#" class="list-group-item list-group-item-action" data-toggle="modal" data-target="#deleteUserModal">Supprimer un utilisateur</a>
        <a href="#" class="list-group-item list-group-item-action" data-toggle="modal" data-target="#createEventModal">Créer un nouvel événement</a>
        <a href="#" class="list-group-item list-group-item-action" data-toggle="modal" data-target="#updateEventStatusModal">Modifie le statut d'un évènement</a>
        <a href="#" class="list-group-item list-group-item-action" data-toggle="modal" data-target="#deleteEventModal">Supression d'un évènement</a>
        <a href="#" class="list-group-item list-group-item-action" data-toggle="modal" data-target="#deleteCategoryModal">Supprimer une catégorie</a>
    </div>
</div>

<!-- Modal pour la création d'un nouvel utilisateur -->
<div class="modal fade" id="createUserModal" tabindex="-1" role="dialog" aria-labelledby="createUserModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createUserModalLabel">Créer un nouvel utilisateur</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Formulaire de création d'utilisateur -->
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <label>Nom d'utilisateur</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Mot de passe</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Rôle</label>
                        <select name="role" class="form-control" required>
                            <option value="user">Utilisateur</option>
                            <option value="admin">Administrateur</option>
                        </select>
                    </div>
                    <input type="hidden" name="action" value="createUser">
                    <button type="submit" class="btn btn-primary">Créer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour la suppression d'un utilisateur -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" role="dialog" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteUserModalLabel">Supprimer un utilisateur</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <label>Nom d'utilisateur à supprimer</label>
                        <select name="usernameToDelete" class="form-control" required>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo htmlspecialchars($user['username']); ?>"><?php echo htmlspecialchars($user['username']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="hidden" name="action" value="deleteUser">
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour la modification de rôle d'un utilisateur -->
<div class="modal fade" id="modifyUserRoleModal" tabindex="-1" role="dialog" aria-labelledby="modifyUserRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modifyUserRoleModalLabel">Modifier le rôle d'un utilisateur</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <label>Nom d'utilisateur à modifier</label>
                        <select name="userIdToModify" class="form-control" required>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['username']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nouveau Rôle</label>
                        <select name="newRole" class="form-control" required>
                            <option value="user">Utilisateur</option>
                            <option value="admin">Administrateur</option>
                        </select>
                    </div>
                    <input type="hidden" name="action" value="modifyUserRole">
                    <button type="submit" class="btn btn-primary">Modifier</button>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Modal pour la création d'un évènement -->
<div class="modal fade" id="createEventModal" tabindex="-1" role="dialog" aria-labelledby="createEventModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createEventModalLabel">Créer un nouvel événement</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Formulaire de création d'événement -->
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <label>Nom de l'événement</label>
                        <input type="text" name="eventName" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="eventDescription" class="form-control" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Date de début</label>
                        <input type="date" name="startDate" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Date de fin</label>
                        <input type="date" name="endDate" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Statut de l'évènement</label>
                        <select name="statut" class="form-control" required>
                            <option value="prévisionnel">Prévisionnel</option>
                            <option value="en cours">En cours</option>
                            <option value="passé">Passé</option>
                        </select>
                    </div>
                    <input type="hidden" name="action" value="createEvent">
                    <button type="submit" class="btn btn-primary">Créer l'événement</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour la mise à jour du statut de l'événement -->
<div class="modal fade" id="updateEventStatusModal" tabindex="-1" role="dialog" aria-labelledby="updateEventStatusModalLabel">
    <div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
        <h5 class="modal-title" id="updateEventStatusModalLabel">Mettre à jour le statut de l'événement</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
        </div>
        <div class="modal-body">
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
            <label for="eventSelect">Événement</label>
            <select id="eventSelect" name="eventId" class="form-control">
                <?php foreach ($events as $event): ?>
                <option value="<?php echo $event['id']; ?>"><?php echo htmlspecialchars($event['nom']); ?></option>
                <?php endforeach; ?>
            </select>
            </div>
            <div class="form-group">
            <label for="statusSelect">Statut</label>
            <select id="statusSelect" name="newStatus" class="form-control">
                <option value="prévisionnel">Prévisionnel</option>
                <option value="en cours">En cours</option>
                <option value="passé">Passé</option>
            </select>
            </div>
            <button type="submit" class="btn btn-primary">Mettre à jour</button>
            <input type="hidden" name="action" value="updateEventStatus">
        </form>
        </div>
    </div>
    </div>
</div>
<!-- Modal pour la suppression d'un événement -->
<div class="modal fade" id="deleteEventModal" tabindex="-1" role="dialog" aria-labelledby="deleteEventModalLabel">
    <div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
        <h5 class="modal-title" id="deleteEventModalLabel">Supprimer un événement</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
        </div>
        <div class="modal-body">
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
            <label for="eventToDeleteSelect">Sélectionnez l'événement à supprimer</label>
            <select id="eventToDeleteSelect" name="eventIdToDelete" class="form-control">
                <?php foreach ($events as $event): ?>
                <option value="<?php echo $event['id']; ?>"><?php echo htmlspecialchars($event['nom']); ?></option>
                <?php endforeach; ?>
            </select>
            </div>
            <button type="submit" class="btn btn-danger">Supprimer l'événement</button>
            <input type="hidden" name="action" value="deleteEvent">
        </form>
        </div>
    </div>
    </div>
</div>
<!-- Modal pour la suppression d'une catégorie -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1" role="dialog" aria-labelledby="deleteCategoryModalLabel">
    <div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
        <h5 class="modal-title" id="deleteCategoryModalLabel">Supprimer une catégorie</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
        </div>
        <div class="modal-body">
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
            <label for="eventSelectCategory">Événement</label>
            <select id="eventSelectCategory" name="eventId" class="form-control" onchange="updateCategories(this.value)">
                <option value="">Sélectionnez un événement</option>
                <?php foreach ($events as $event): ?>
                <option value="<?php echo $event['id']; ?>"><?php echo htmlspecialchars($event['nom']); ?></option>
                <?php endforeach; ?>
            </select>
            </div>
            <div class="form-group">
            <label for="categoryToDeleteSelect">Catégorie</label>
            <select id="categoryToDeleteSelect" name="categoryIdToDelete" class="form-control">
                <!-- Les options de catégorie seront chargées ici -->
            </select>
            </div>
            <input type="hidden" name="action" value="deleteCategory">
            <button type="submit" class="btn btn-danger">Supprimer la catégorie</button>
        </form>
        </div>
    </div>
    </div>
</div>

<footer class="footer" style="background-color: <?= htmlspecialchars($themeColor); ?>">
    &copy; <?= date('Y'); ?> CTF Events. Tous droits réservés.
</footer>


<!-- Bootstrap JS et dépendances -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="js/themeSwitcher.js"></script>
<script>
function updateCategories(eventId) {
    fetch('get_categories.php?eventId=' + eventId)
        .then(response => response.json())
        .then(data => {
            const categorySelect = document.getElementById('categoryToDeleteSelect');
            categorySelect.innerHTML = '';
            data.forEach(category => {
                const option = document.createElement('option');
                option.value = category.id;
                option.textContent = category.nom;
                categorySelect.appendChild(option);
            });
        })
        .catch(error => console.error('Error:', error));
}
</script>
</body>
</html>
