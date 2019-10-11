
# jeedom-gardenasmartsystem
Plugin Jeedom permettant de gérer les équipements Gardena Smart System (tondeuses et sondes uniquement pour l'instant)

Installation
===

Etape 1 Créer un compte Gardena Smart System
------------
**Note!**
Si vous avez dejà un compte  GARDENA smart system, vous pouvez vous connecter (**Sign In**)  avec ce compte et passer à l' **Etape 2**,  [Créer une application](https://developer.1689.cloud/docs/getting-started#2create-application).

Pour créer un compte sur le Portail Husqvarna Group Developer, sélectionez **Sign Up** dans le menu à droite. Renseignez le formulaire et  selectionnez **Sign Up**.

#### Verify Your Account

Un email vous est envoyé à l'adresse indiquée dans le formulaire. A la réception de ce mail, cliquez sur le lien pour activer votre compte. Vous serez alors redirigé vers une page d'activation de compte.

Une fois votre compte activé, vous devriez pouvoir vous connecter.

Etape 2 Créer une application
------------
A l'aide de vos identifiants Gardena,  connectez-vous sur [https://developer.1689.cloud/](https://developer.1689.cloud/).

### Créer une nouvelle application
Une fois connecté, cliquez **My Applications** dans le menu. Pour créer une nouvelle application, cliquez sur le bouton **Create application**.
 
Renseignez le formulaire avec les informations suivantes :

 - Name: Jeedom Gardena Plugin
 - Description: Jeedom Gardena Smart System plugin
 - Redirect URIs: laisser vide
 
### Connectez des APIs à l'application

Pour pouvoir utilser cette application, il faut la connecter aux APIs. Cliquez sur le bouton  **Connect new API**. Sélectionnez les APIs **Authentication API** et **GARDENA smart system API**.

Récupérez votre client-id (champ Application key) et client-secret (champ Application secret).

Etape 3 Configurez le plugin
------------
Depuis la page de configuration du plugin, collez vos login, mot de passe, client-id et votre client-secret. et sauvegardez. Vos équipements sont détectés automatiquement.

Utilisation
===
Pour chaque équipement activé vous aurez un widget sur votre dashboard

Contributeurs
------------
- [koleos6](https://github.com/koleos6), gestion des sondes


