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

## Start and stop action of the equipment

### Local mode

It is necessary to give the rights to the user "www-data" to launch the commands "reboot" and "poweroff". To do this, two methods:

----
- give the "root" rights to the user "www-data" (solution recommended by Jeedom)

sudo su -
echo "www-data ALL=(ALL) NOPASSWD: ALL" | (EDITOR="tee -a" visudo)

----
- give the user "www-data" the "root" rights only for the "reboot" and "poweroff" commands

sudo su -
echo "www-data ALL=NOPASSWD:/sbin/reboot" | (EDITOR="tee -a" visudo) && echo "www-data ALL=NOPASSWD:/sbin/poweroff" | (EDITOR="tee -a" visudo)
----

### Deported mode

When setting up the equipment, simply select an SSH identifier and password with sufficient rights to launch both reboot and poweroff commands.

# Some catches
![GitHub Logo](/../images/Monitoring8.png)

# FAQ
### What is the frequency of refreshing the statutes?
The plugin updates the information every 5 minutes (modifiable in the "Task Engine")

### What is the frequency of refreshing information?
The plugin updates the information every 15min (00, 15, 30 and 45).

### What is the meaning of Load average?
For more information: http://fr.wikipedia.org/wiki/Load_average[Wikipedia]

### Is it possible to Monitor VMware ESXi?
To this day, ESXi does not use traditional linux commands. If a strong demand is felt, why not ...

### While updating the plugin, I get an error:
cat: /sys/devices/platform/sunxi-i2c.0/i2c-0/0-0034/temp1_input: No such file or directory
If you have Jeedom Cubieboard. Ignoring this error, it captures the temperature of the Banana Pi (which is recognized as a Cubieboard).
No problem on the plugin operation

### I get errors in cron_execution when recovering the CPU temperature
For example: cat: / sys / devices / virtual / thermal / thermal_zone0 / temp: No file or folder of this type
Your equipment is certainly incompatible to recover this data. To avoid error logs, simply uncheck: "display" on the "CPU temperature" command of your equipment.
And for immediate consideration, you must make 2 backups of the configuration.

# Changelog
- 09-2019 : add Panel Mobile
- 07-2018 : adding a dedicated cron
- 06-2018: correction of free memory and percentage for Debian 9 (stretch). And adding the choice of subtype for "Custom" commands
- 10-2017: removal of expert mode
- 09-2017: adding Edgerouter compatibility and info.xml removal
- 05-2017: addition of the possibility to check (or not) "Show" on the line "CPU temperature"
- 04-2017: Fixed error in the logs for CPU temperature
- 12-2016: fixed Chrome version 55
- bug fix with advanced display
- bootstrapdwitch removal
