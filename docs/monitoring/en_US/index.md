# Presentation of the Monitoring plugin

This plugin allows you to recover system data and display it on the Dashboard.

Initially, this plugin was created to monitor only Jeedom Mini and Jeedom Mini +. But to meet a high demand, it has been made compatible with a Jeedom installed on a VM (Virtual Machine) or a Synology NAS or etc ...

# Compatibility

To my knowledge, here is a non-exhaustive list of hardware / compatible distribution:

- Jeedom Mini, Mini+ (surely the Smart/Pro)
- Raspberry Pi
- Cubieboard
- Banana Pi
- Machine Virtuelle (VM)
- NAS Synology
- Linux (should be compatible with a lot of Linux distribution)
- Specific distributions : OpenELEC, LibreElec, RasPlex et OSMC

# Data visible on the Dashboard :

- **Distribution**: Name and version of the Linux operating system, 32 or 64 bit architecture, and the microprocessor type
- **Started since uptime**: inform for how long Jeedom is started
- **System load**: Load average (more information see FAQ)
- **Memory**: total RAM and used
- **Network**: MiB rate of transmitted and received data
- **Total and used disk space**: Disk space of SD card or hard disk for Synology VMs and NAS
- **CPU**: number of core, associated frequency and temperature (temperature only if compatible)
- **Custom Commands**: lets you enter custom commands is to display / historize the results

![GitHub Logo](/../images/Monitoring.png)

# Configuration

We will now configure a device. To do this, click on "Plugins/Jeedom Box/Monitoring*

Then click on the button at the top left "Add equipment"

![GitHub Logo](/../images/Monitoring1.png)

Then enter the name of the equipment (eg Jeedom Mini)

![GitHub Logo](/monitoring/images/Monitoring2.png)

Then define:

- Parent object
- Category (optional)
- Activate (check, otherwise the equipment will not be usable)
- Visible (optional if you do not want to make it visible on the Dashboard)

![GitHub Logo](/../images/Monitoring3.png)

And select if Jeedom is local or deported

![GitHub Logo](/../images/Monitoring9.png)

Local :: allows to monitor the Jeedom on which the plugin is installed (locally)
Remote :: allows to monitor a remote Jeedom (installed on another machine)

## Deported Choice
After selecting this mode, 4 additional fields are displayed:

IP address :: enter the IP address of the remote machine
SSH port :: enter SSH port number (default is port 22)
ID :: enter the username that will be used to launch Linux commands
Password :: enter the password that is associated with the username

![GitHub Logo](/../images/Monitoring4.png)

> **[IMPORTANT]**
> You must choose an identifier with the necessary rights to launch the commands (usually the login "root").
> For a Synology NAS, use the login with administrator rights.

## Staining of values
To highlight values, it is possible to colorize certain values.

The values must match a value visible on the Dashboard. Example: 55 ° C, 12% etc ... It will enter only the encrypted value, without the sign%, ° C etc ...

![GitHub Logo](/../images/Monitoring5.png)

## historicize
For some values, it is possible to activate "historiser" to represent, by a curve, the variations of different values.

Historiser is possible for:

- System load (Load average)
- Free memory (percentage)
- Free disk space (percentage)
- CPU temperature (only with Jeedom Mini)

![GitHub Logo](/../images/Monitoring6.png)

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
![GitHub Logo](/../images/Monitoring8.png)

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
