# Chemin pour télécharger les fichiers
$downloadPath = "$PSScriptRoot\downloads"
$wampInstallerUrl = "https://sourceforge.net/projects/wampserver/files/latest/download"
$mysqlRootPassword = ""
$dbName = ""
$dbUser = ""
$dbPass = ""
$adminUser = ""
$adminEmail = ""
$adminPass = ""
$siteName = ""
$siteAdminEmail = ""
$siteDomain = ""

# Créer le répertoire de téléchargement si non existant
if (-Not (Test-Path $downloadPath)) {
    New-Item -ItemType Directory -Path $downloadPath
}

# Téléchargement de WAMP
Write-Output "Téléchargement de WAMP..."
Invoke-WebRequest -Uri $wampInstallerUrl -OutFile "$downloadPath\wampinstaller.exe"

# Installation de WAMP
Write-Output "Installation de WAMP..."
Start-Process -FilePath "$downloadPath\wampinstaller.exe" -ArgumentList "/VERYSILENT" -Wait

# Attente pour s'assurer que WAMP est installé
Start-Sleep -Seconds 30

# Configurer MySQL avec mot de passe root
function Configure-MySQLRoot {
    param (
        [string]$rootPassword
    )
    &"$env:WINDIR\system32\cmd.exe" /c "cd /d C:\wamp64\bin\mysql\mysql* && bin\mysqladmin.exe -u root password $rootPassword"
}

# Créer la base de données et l'utilisateur MySQL
function Create-MySQLDatabaseAndUser {
    param (
        [string]$rootPassword,
        [string]$dbName,
        [string]$dbUser,
        [string]$dbPass
    )
    &"$env:WINDIR\system32\cmd.exe" /c "cd /d C:\wamp64\bin\mysql\mysql* && bin\mysql.exe -u root -p$rootPassword -e 'CREATE DATABASE $dbName; CREATE USER \"$dbUser\"@\"localhost\" IDENTIFIED BY \"$dbPass\"; GRANT ALL PRIVILEGES ON $dbName.* TO \"$dbUser\"@\"localhost\"; FLUSH PRIVILEGES;'"
}

# Importer le fichier structure.sql
function Import-StructureSQL {
    param (
        [string]$dbUser,
        [string]$dbPass,
        [string]$dbName,
        [string]$structureFile
    )
    &"$env:WINDIR\system32\cmd.exe" /c "cd /d C:\wamp64\bin\mysql\mysql* && bin\mysql.exe -u $dbUser -p$dbPass $dbName < $structureFile"
}

# Créer l'utilisateur administrateur
function Create-AdminUser {
    param (
        [string]$dbUser,
        [string]$dbPass,
        [string]$dbName,
        [string]$adminUser,
        [string]$adminEmail,
        [string]$adminPass
    )
    $hashedPass = &php -r "echo password_hash('$adminPass', PASSWORD_DEFAULT);"
    &"$env:WINDIR\system32\cmd.exe" /c "cd /d C:\wamp64\bin\mysql\mysql* && bin\mysql.exe -u $dbUser -p$dbPass $dbName -e 'INSERT INTO users (username, password, email, role) VALUES (\"$adminUser\", \"$hashedPass\", \"$adminEmail\", \"admin\");'"
}

# Mettre à jour le fichier db.php avec les informations de l'utilisateur
function Update-DBPHP {
    param (
        [string]$dbFilePath,
        [string]$dbUser,
        [string]$dbPass,
        [string]$dbName
    )
    (Get-Content $dbFilePath) -replace "'changemeuser'", "'$dbUser'" -replace "'changemepassword'", "'$dbPass'" -replace "'changemedb'", "'$dbName'" | Set-Content $dbFilePath
}

# Configurer le site Apache
function Configure-ApacheSite {
    param (
        [string]$siteName,
        [string]$siteAdminEmail,
        [string]$siteDomain,
        [string]$siteDir
    )
    $apacheConfPath = "C:\wamp64\bin\apache\apache2.4.41\conf\extra\httpd-vhosts.conf"
    $siteConfig = @"
<VirtualHost *:80>
    ServerAdmin $siteAdminEmail
    ServerName $siteDomain
    ServerAlias $siteDomain
    RedirectMatch ^/$ /login.php
    DocumentRoot $siteDir
    ErrorLog "logs/$siteName-error.log"
    CustomLog "logs/$siteName-access.log" common
</VirtualHost>
"@
    Add-Content -Path $apacheConfPath -Value $siteConfig
}

# Ajouter une entrée dans le fichier hosts
function Add-HostsEntry {
    param (
        [string]$siteDomain
    )
    $ipAddress = (Get-NetIPAddress -AddressFamily IPv4).IPAddress[0]
    Add-Content -Path "C:\Windows\System32\drivers\etc\hosts" -Value "$ipAddress $siteDomain"
}

# Démarrage du service Apache
function Start-Apache {
    &"$env:WINDIR\system32\cmd.exe" /c "net start wampapache64"
}

# Demander à l'utilisateur de saisir les informations nécessaires
function Prompt-UserForDetails {
    param (
        [string]$prompt,
        [switch]$isSecure
    )
    if ($isSecure) {
        return Read-Host -Prompt $prompt -AsSecureString | ConvertFrom-SecureString | ConvertTo-SecureString
    } else {
        return Read-Host -Prompt $prompt
    }
}

# Exécution des fonctions
$mysqlRootPassword = Prompt-UserForDetails -prompt "Entrez le mot de passe root pour MySQL" -isSecure
Configure-MySQLRoot -rootPassword $mysqlRootPassword

$dbName = Prompt-UserForDetails -prompt "Entrez le nom de la base de données"
$dbUser = Prompt-UserForDetails -prompt "Entrez le nom de l'utilisateur MySQL"
$dbPass = Prompt-UserForDetails -prompt "Entrez le mot de passe pour l'utilisateur MySQL" -isSecure

Create-MySQLDatabaseAndUser -rootPassword $mysqlRootPassword -dbName $dbName -dbUser $dbUser -dbPass $dbPass

$structureFilePath = "$PSScriptRoot\SQL\structure.sql"
Import-StructureSQL -dbUser $dbUser -dbPass $dbPass -dbName $dbName -structureFile $structureFilePath

$adminUser = Prompt-UserForDetails -prompt "Entrez le nom d'utilisateur pour l'administrateur"
$adminEmail = Prompt-UserForDetails -prompt "Entrez l'email de l'administrateur"
$adminPass = Prompt-UserForDetails -prompt "Entrez le mot de passe pour l'administrateur" -isSecure

Create-AdminUser -dbUser $dbUser -dbPass $dbPass -dbName $dbName -adminUser $adminUser -adminEmail $adminEmail -adminPass $adminPass

$siteName = Prompt-UserForDetails -prompt "Entrez le nom du site (ex: monsite)"
$siteAdminEmail = Prompt-UserForDetails -prompt "Entrez l'email de l'administrateur pour Apache"
$siteDomain = Prompt-UserForDetails -prompt "Entrez le nom de domaine ou l'alias pour Apache (ex: www.monsite.com)"

$siteDir = "C:\wamp64\www\$siteName"
New-Item -ItemType Directory -Path $siteDir
Copy-Item -Path "$PSScriptRoot\php\*" -Destination $siteDir

$dbFilePath = "$siteDir\db.php"
Update-DBPHP -dbFilePath $dbFilePath -dbUser $dbUser -dbPass $dbPass -dbName $dbName

Configure-ApacheSite -siteName $siteName -siteAdminEmail $siteAdminEmail -siteDomain $siteDomain -siteDir $siteDir
Add-HostsEntry -siteDomain $siteDomain
Start-Apache

Write-Output "Installation et configuration de WAMP terminées."
