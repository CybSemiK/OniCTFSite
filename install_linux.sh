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

#installer expect
install_if_not_exists expect

# Installer Apache
install_if_not_exists apache2

# Installer MySQL
install_if_not_exists mysql-server

# Demander à l'utilisateur le mot de passe root pour MySQL
while true; do
    read -s -p "Entrez le mot de passe root pour MySQL : " MYSQL_ROOT_PASSWORD
    echo
    read -s -p "Confirmez le mot de passe root pour MySQL : " MYSQL_ROOT_PASSWORD_CONFIRM
    echo
    [ "$MYSQL_ROOT_PASSWORD" = "$MYSQL_ROOT_PASSWORD_CONFIRM" ] && break
    echo "Les mots de passe ne correspondent pas. Veuillez réessayer."
done

# Configurer MySQL pour utiliser l'authentification par mot de passe pour l'utilisateur root
sudo mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH 'mysql_native_password' BY '${MYSQL_ROOT_PASSWORD}'; FLUSH PRIVILEGES;"

# Sécuriser l'installation de MySQL automatiquement
SECURE_MYSQL=$(expect -c "

set timeout 10
spawn sudo mysql_secure_installation

expect \"Enter current password for root (enter for none):\"
send \"${MYSQL_ROOT_PASSWORD}\r\"

expect \"Switch to unix_socket authentication\"
send \"n\r\"

expect \"Change the root password?\"
send \"n\r\"

expect \"Remove anonymous users?\"
send \"y\r\"

expect \"Disallow root login remotely?\"
send \"y\r\"

expect \"Remove test database and access to it?\"
send \"y\r\"

expect \"Reload privilege tables now?\"
send \"y\r\"

expect eof
")

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
sudo mysql -u root -p${MYSQL_ROOT_PASSWORD} -e "CREATE DATABASE ${DB_NAME};"
sudo mysql -u root -p${MYSQL_ROOT_PASSWORD} -e "CREATE USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
sudo mysql -u root -p${MYSQL_ROOT_PASSWORD} -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
sudo mysql -u root -p${MYSQL_ROOT_PASSWORD} -e "FLUSH PRIVILEGES;"

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

# Créer le répertoire nécessaire aux uploads et les droits associés
sudo mkdir "${SITE_DIR}/"uploads/
sudo mkdir "${SITE_DIR}/"uploads/writeups
sudo chown -R www-data:www-data "${SITE_DIR}/"uploads/
sudo chmod -R 766 "${SITE_DIR}/"uploads/

# Mettre à jour le fichier db.php avec les informations de l'utilisateur
DB_PHP_FILE="${SITE_DIR}/db.php"

sudo sed -i "s/'changemeuser'/'${DB_USER}'/g" "${DB_PHP_FILE}"
sudo sed -i "s/'changemepassword'/'${DB_PASS}'/g" "${DB_PHP_FILE}"
sudo sed -i "s/'changemedb'/'${DB_NAME}'/g" "${DB_PHP_FILE}"

# Configurer le fichier de configuration Apache
APACHE_CONF="/etc/apache2/sites-available/${SITE_NAME}.conf"

sudo a2enmod ssl
sudo a2enmod rewrite
sudo systemctl restart apache2

sudo bash -c "cat > ${APACHE_CONF}" <<EOL
<VirtualHost *:80>
    ServerAdmin ${SITE_ADMIN_EMAIL}
    ServerName ${SITE_DOMAIN}
    ServerAlias ${SITE_DOMAIN}
    Redirect permanent / https://${SITE_DOMAIN}/login.php
    DocumentRoot ${SITE_DIR}
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>

<VirtualHost *:443>
    ServerAdmin ${SITE_ADMIN_EMAIL}
    ServerName ${SITE_DOMAIN}
    ServerAlias ${SITE_DOMAIN}
    DocumentRoot ${SITE_DIR}

    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/apache-selfsigned.crt
    SSLCertificateKeyFile /etc/ssl/private/apache-selfsigned.key

    <Directory ${SITE_DIR}>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
EOL

# Créer et configurer le fichier .htaccess
HTACCESS_FILE="${SITE_DIR}/.htaccess"
sudo bash -c "cat > ${HTACCESS_FILE}" <<EOL
RewriteEngine On

RewriteCond %{HTTPS} !=on
RewriteRule ^/?(.*) https://${SITE_DOMAIN}/$1 [R=301,L]

RewriteCond %{REQUEST_URI} !^/login.php$
RewriteCond %{REQUEST_URI} !\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ [NC] # Exclure les fichiers statiques
RewriteRule ^.*$ /login.php [R=302,L]
EOL

sudo chown www-data:www-data "${HTACCESS_FILE}"
sudo chmod 644 "${HTACCESS_FILE}"


sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout /etc/ssl/private/apache-selfsigned.key -out /etc/ssl/certs/apache-selfsigned.crt

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
