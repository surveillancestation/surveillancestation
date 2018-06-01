# Présentation

Ce plugin permet de récupérer des données système et de les afficher sur le Dashboard.

Initialement, ce plugin a été créé pour monitorer seulement Jeedom Mini et Jeedom Mini+. Mais pour répondre à une forte demande, il a été rendu compatible avec un Jeedom installé sur une VM (Machine Virtuelle) ou un NAS Synology ou etc...

# Compatibilité

A ma connaissance, voici une liste non exhautive des hardware/distribution compatible:

- Jeedom Mini, Mini+ (surement la Center/Pro)
- Raspberry Pi
- Cubieboard
- Banana Pi
- Machine Virtuelle (VM)
- NAS Synology
- Linux (devrait être compatible avec beaucoup de distribution Linux)
- Distributions spécifiques : OpenELEC, LibreElec, RasPlex et OSMC

# Données visibles sur le Dashboard :

- **Distribution** : Nom et version du système d'exploitation Linux, architecture 32 ou 64 bits, et le type microprocesseur
- **Démarré depuis uptime** : informe depuis combien de temps Jeedom est démarré
- **Charge système** : Load average (plus d'information voir FAQ)
- **Mémoire** : mémoire vive total et utilisée
- **Réseau** : taux en MiB des données transmises et reçues
- **Espace disque total et utilisé** : l'espace disque de la carte SD ou du disque dur pour les VM et NAS Synology
- **CPU** : nombre de cœur, la fréquence associée et la température (température seulement si compatible)
- **Commandes personnalisées** : permet de saisir des commandes personnalisées est d'afficher/historiser les résultats

![GitHub Logo](/../images/Monitoring.png)

# Configuration

Nous allons maintenant paramétrer un équipement. Pour se faire, cliquer sur *Plugins / Jeedom Box / Monitoring*

Puis cliquer sur le bouton en haut à gauche *Ajouter un équipement*

![GitHub Logo](/monitoring/images/Monitoring1.png)

Puis saisir le nom de l'équipement (ex. Jeedom Mini)

![GitHub Logo](/monitoring/images/Monitoring2.png)

Puis définir :

- Objet parent
- Catégorie (optionnelle)
- Activer (à cocher, sinon l’équipement ne sera pas utilisable)
- Visible (optionel si vous ne désirez pas le rendre visible sur le Dashboard)

![GitHub Logo](/monitoring/images/Monitoring3.png)

Et sélectionner si Jeedom est local ou déporté

![GitHub Logo](/monitoring/images/Monitoring9.png)

Local:: permet de surveiller le Jeedom sur lequel est installé le plugin (localement)
Déporté:: permet de monitorer un Jeedom distant (installé sur une autre machine)

## Choix déporté

Après avoir sélectionné ce mode, 4 champs supplémentaires s'affichent :

Adresse IP:: saisir l'adresse IP de la machine distante
Port SSH:: saisir le numéro du port SSH (par défaut il s'agit du port 22)
Identifiant:: saisir le nom d'utilisateur qui sera utilisé pour lancer les commandes Linux
Mot de passe:: saisir le mot de passe qui est associé au nom d'utilisateur

![GitHub Logo](/monitoring/images/Monitoring4.png)

> **[IMPORTANT]**
> Vous devez choisir un identifiant avec les droits nécessaires pour lancer les commandes (en général le login "root").
> Pour un NAS Synology, il faut utiliser le login disposant des droits administrateur.

## Coloration des valeurs
Pour mettre en avant des valeurs, il est possible de coloriser certaines valeurs.

Les valeurs doivent correspondre à une valeur visible sur le Dashboard. Exemple : 55°C, 12% etc... Il faudra saisir seulement la valeur chiffré, sans le signe %, °C etc...

![GitHub Logo](/monitoring/images/Monitoring5.png)

## Historiser
Pour certaines valeurs, il est possible d'activer "historiser" pour représenter, par une courbe, les variations de différentes valeurs.

Historiser est possible pour :

- Charge système (Load average)
- Mémoire libre (pourcentage)
- Espace disque libre (porcentage)
- Température CPU (seulement avec Jeedom Mini)

![GitHub Logo](/monitoring/images/Monitoring6.png)

## Action redémarrage et extinction de l'équipemnt

### Mode local

Il est nécessaire de donner les droits à l'utilisateur "www-data" de lancer les commandes "reboot" et "poweroff". Pour ce faire, deux méthodes :

- soit donner les droits "root" à l'utilisateur "www-data" (solution préconisée par Jeedom)

----
sudo su -
echo "www-data ALL=(ALL) NOPASSWD: ALL" | (EDITOR="tee -a" visudo)
----

- soit donner à l'utilisateur "www-data" les droits "root" seulement pour les commandes "reboot" et "poweroff"

----
sudo su -
echo "www-data ALL=NOPASSWD:/sbin/reboot" | (EDITOR="tee -a" visudo) && echo "www-data ALL=NOPASSWD:/sbin/poweroff" | (EDITOR="tee -a" visudo)
----

### Mode déporté

Il suffit, lors de la configuration de l'équipement, de choisir un identifiant et mot de passe SSH avec suffisamment de droit pour lancer les deux commandes "reboot" et "poweroff"

=== Quelques captures
![GitHub Logo](/monitoring/images/Monitoring8.png)

# FAQ
### Quelle est la fréquence de rafraichissement des statuts ?
Le plugin actualise les informations toutes les 5 minutes (modifiable dans le "Moteur de tâches")

### Quelle est la fréquence de rafraichissement des informations ?
Le plugin actualise les informations toutes les 15min (00, 15, 30 et 45).

### Quelle est la signification de Charge système (Load average) ?
Pour plus d'information : http://fr.wikipedia.org/wiki/Load_average[Wikipédia]

### Est-il possible de Monitorer VMware ESXi ?
A ce jour non, ESXi n'utilise pas les commandes linux classiques. Si une forte demande se fait ressentir, pourquoi pas...

### Pendant la mise à jour du plugin, j'obtiens une erreur :
cat: /sys/devices/platform/sunxi-i2c.0/i2c-0/0-0034/temp1_input: No such file or directory
Si vous avez Jeedom Cubieboard. Ne pas tenir compte de cette erreur, elle permet de capter la température du Banana Pi (qui est reconnu comme un Cubieboard).
Aucun problème sur le fonctionnement du plugin

### J'obtiens des erreurs dans cron_execution lors de la récupération de la température CPU
Comme par exemple  : cat: /sys/devices/virtual/thermal/thermal_zone0/temp: Aucun fichier ou dossier de ce type
Votre équipement est certainement incompatible pour récupérer cette donnée. Pour éviter les logs d'erreurs, il suffit de décocher : "afficher" sur la commande "Température CPU" de votre équipement.
Et pour une prise en compte immédiate, il faut effectuer 2 sauvegardes de la configuration.

# Changelog
- 06-2018 : correction de la mémoire libre et du pourcentage pour Debian 9 (stretch)
- 10-2017 : suppression du mode expert
- 09-2017 : ajout compatibilité Edgerouter et suppression info.xml
- 05-2017 : ajout de la possibilité de cocher (ou pas) "Afficher" sur la ligne "Température CPU"
- 04-2017 : fixe erreur dans les logs pour la température CPU
- 12-2016 : fixe Chrome version 55
- correction bug avec l'affichage avancé
- suppression bootstrapdwitch
