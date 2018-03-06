[panel,primary]
.Quelle est la fréquence de rafraichissement des statuts ?
--
Le plugin actualise les informations toutes les 5 minutes (modifiable dans le "Moteur de tâches")
--

[panel,primary]
.Je ne vois pas mes positions prédéfinies, et mes patrouilles lors de la création d'un scénario :
--
- dès que vous créez une nouvelle position ou patrouille, il faut relancer une synchronisation via le plugin. Permet de remettre à jour la liste dans vos scénarios.
--

[panel,primary]
.J’obtiens une erreur quand je demande l'activation ou la désactivation de la caméra ou un code erreur 117 :
--
- l'identifiant n'a certainement pas les bons privilèges dans Surveillance Station. Modifier le privilège de spectateur à directeur
--

[panel,primary]
.J’obtiens un code erreur 105 :
--
- l'identifiant n'a pas les droits pour utiliser l'application Surveillance Station (panneau de config / Utilisateur / modifier l'utilisateur / onglet Application / cocher Surveillance Station)
--

[panel,primary]
.J’obtiens un code erreur 401 :
--
- l'identifiant est sûrement désactivé dans Surveillance Station. Je vous conseil d'utiliser un identifiant unique avec les droits : dossier "surveillance" dans "permissions", "Surveillance Station" dans "Applications" et un privilège directeur dans Surveillance Station
--

[panel,primary]
.J’obtiens un code erreur 407 :
--
- l'identifiant est bloqué (panneau de config / Sécurité / onglet compte / Autoriser/Bloquer la liste / onglet Liste des blogages)
--

[panel,primary]
.J’obtiens une erreur : Connection refused
--
- Vérifiez bien que l'adresse et le port correspondent bien à votre Synology, et non à Surveillance Station
--

[panel,primary]
.L’affiche du Live déborde du widget (ou trop grand/petit), je désire redimensionner la taille. Comment faire ?
--
Vous pouvez redimensionner la taille du widget avec le crayon en haut à droite sur le Dashboard.
--

[panel,primary]
.Le redimentionnement du Widget de ma caméra Live ne fonctionne pas. Que faire ?
--
Le redimensionnement est effectif seulement après actualisation de la page. Pour faciliter le réglage, je vous conseille de choisir une taille du Widget "caméra désactivée". Et de réactiver la caméra, puis d’actualiser à nouveau de Dashboard.
--

[panel,primary]
.En HTTPS, le live de la caméra ne s’affiche pas. Que faire ?
--
Vous avez certainement un certificat auto-signé (pour le vérifier, dans DSM / Panneau de configuration / Sécurité / certificat). Dans ce cas, le plugin n’est pas compatible (il est toutefois possible d’ajouter une exception dans votre navigateur Internet, mais cette solution risque de ne pas fonctionner sur votre mobile). Je vous conseille de passer par une autorité de certification. Il existe par exemples "StartSSL", "CAcert" et "Let's Encrypt" qui proposent un certificat valide et gratuit (à renouveler une fois de temps en temps suivant l'autorité)
--

[panel,primary]
.L’activation et la désactivation de la caméra ne fonctionnent pas. Que faire ?
--
Vérifier les privilèges de l’utilisateur dans Surveillance Station (surement que Spectateur, à changer en Directeur).
--

[panel,primary]
.Impossible de désactiver ou d’activer la détection de mouvement. Que faire ?
--
L’activation ou la désactivation fonctionne seulement quand la caméra est activée. Il faut donc activer la caméra avant de modifier ce paramètre.
--
