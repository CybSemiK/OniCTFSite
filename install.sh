#!/bin/bash

# Fonction pour installer un paquet si non installé
install_if_not_exists() {
    dpkg -s "$1" &> /dev/null

    if [ $? -ne 0 ]; then
        echo "Installation de $1..."
        sudo apt install -y "$1"
    else
        echo "$1 est déjà installé."
    fi
}

# Mettre à jour les paquets
sudo apt update
sudo apt upgrade -y

# Installer Apache
install_if_not_exists apache2

# Installer MySQL
install_if_not_exists mysql-server

# Sécuriser l'installation de MySQL
sudo mysql_secure_installation

# Installer PHP
install_if_not_exists php
install_if_not_exists libapache2-mod-php
install_if_not_exists php-mysql

# Redémarrer Apache pour prendre en compte PHP
sudo systemctl restart apache2

# Demander à l'utilisateur les informations nécessaires pour MySQL
read -p "Entrez le nom de la base de données : " DB_NAME
read -p "Entrez le nom de l'utilisateur MySQL : " DB_USER

# Demander le mot de passe et le confirmer
while true; do
    read -s -p "Entrez le mot de passe pour l'utilisateur MySQL : " DB_PASS
    echo
    read -s -p "Confirmez le mot de passe : " DB_PASS_CONFIRM
    echo
    [ "$DB_PASS" = "$DB_PASS_CONFIRM" ] && break
    echo "Les mots de passe ne correspondent pas. Veuillez réessayer."
done

STRUCTURE_FILE="SQL/structure.sql"

# Créer la base de données et l'utilisateur
sudo mysql -e "CREATE DATABASE ${DB_NAME};"
sudo mysql -e "CREATE USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
sudo mysql -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

# Importer la structure de la base de données
if [ -f "${STRUCTURE_FILE}" ]; then
    sudo mysql -u ${DB_USER} -p${DB_PASS} ${DB_NAME} < ${STRUCTURE_FILE}
    echo "Structure de la base de données importée avec succès."
else
    echo "Le fichier ${STRUCTURE_FILE} n'existe pas. Assurez-vous qu'il se trouve dans le même répertoire que ce script."
    exit 1
fi

# Demander les informations pour l'utilisateur administrateur
read -p "Entrez le nom d'utilisateur pour l'administrateur : " ADMIN_USER
read -p "Entrez l'email de l'administrateur : " ADMIN_EMAIL

while true; do
    read -s -p "Entrez le mot de passe pour l'administrateur : " ADMIN_PASS
    echo
    read -s -p "Confirmez le mot de passe de l'administrateur : " ADMIN_PASS_CONFIRM
    echo
    [ "$ADMIN_PASS" = "$ADMIN_PASS_CONFIRM" ] && break
    echo "Les mots de passe ne correspondent pas. Veuillez réessayer."
done

# Utiliser PHP pour hasher le mot de passe
HASHED_PASS=$(php -r "echo password_hash('${ADMIN_PASS}', PASSWORD_DEFAULT);")

# Insérer l'utilisateur administrateur dans la base de données
INSERT_USER_QUERY="INSERT INTO users (username, password, email, role) VALUES ('${ADMIN_USER}', '${HASHED_PASS}', '${ADMIN_EMAIL}', 'admin');"

sudo mysql -u ${DB_USER} -p${DB_PASS} -D ${DB_NAME} -e "${INSERT_USER_QUERY}"

echo "Utilisateur administrateur créé avec succès."

# Demander les informations pour configurer le site
read -p "Entrez le nom du site (ex: monsite) : " SITE_NAME
read -p "Entrez l'email de l'administrateur pour Apache : " SITE_ADMIN_EMAIL
read -p "Entrez le nom de domaine ou l'alias pour Apache (ex: www.monsite.com) : " SITE_DOMAIN

# Créer le répertoire du site
SITE_DIR="/var/www/${SITE_NAME}"
sudo mkdir -p "${SITE_DIR}"

# Copier les fichiers PHP dans le répertoire du site
sudo cp -r php/* "${SITE_DIR}/"

# Mettre à jour le fichier db.php avec les informations de l'utilisateur
DB_PHP_FILE="${SITE_DIR}/db.php"

sudo sed -i "s/'changemeuser'/'${DB_USER}'/g" "${DB_PHP_FILE}"
sudo sed -i "s/'changemepassword'/'${DB_PASS}'/g" "${DB_PHP_FILE}"
sudo sed -i "s/'changemedb'/'${DB_NAME}'/g" "${DB_PHP_FILE}"

# Configurer le fichier de configuration Apache
APACHE_CONF="/etc/apache2/sites-available/${SITE_NAME}.conf"

sudo bash -c "cat > ${APACHE_CONF}" <<EOL
<VirtualHost *:80>
    ServerAdmin ${SITE_ADMIN_EMAIL}
    ServerName ${SITE_DOMAIN}
    ServerAlias ${SITE_DOMAIN}
    RedirectMatch ^/\$ /login.php
    DocumentRoot ${SITE_DIR}
    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOL

# Activer le nouveau site et désactiver le site par défaut
sudo a2dissite 000-default.conf
sudo a2ensite "${SITE_NAME}.conf"

# Redémarrer Apache pour appliquer les modifications
sudo systemctl reload apache2

# Ajouter l'entrée dans le fichier /etc/hosts
IP_ADDRESS=$(hostname -I | awk '{print $1}')
HOSTS_LINE="${IP_ADDRESS} ${SITE_DOMAIN}"

if ! grep -q "${SITE_DOMAIN}" /etc/hosts; then
    echo "Ajout de ${SITE_DOMAIN} dans /etc/hosts"
    sudo bash -c "echo ${HOSTS_LINE} >> /etc/hosts"
else
    echo "${SITE_DOMAIN} existe déjà dans /etc/hosts"
fi

echo "Installation LAMP, configuration de la base de données, et déploiement du site terminés."
