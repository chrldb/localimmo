# Localimmo – Localisation d’annonces immobilières par DPE

**Localimmo** est une application web qui permet de retrouver la localisation d’annonces immobilières à partir de leur Diagnostic de Performance Énergétique (DPE). En croisant les données DPE ouvertes et les caractéristiques des biens (surface, classe énergétique, année de construction, etc.), le site aide à identifier les adresses possibles des annonces.

## Fonctionnalités principales
- Recherche d’annonces immobilières par critères (DPE, surface, etc.)
- Croisement intelligent avec la base de données des DPE pour estimer une localisation
- Affichage sur carte interactive
- Possibilité d’entrer l’URL d’une annonce provenant de Belles Demeures ou SeLoger
- Un score de confiance est attribué à chaque estimation en fonction de la précision des correspondances DPE
  
## Tech stack
- Frontend / Backend : php
- Données : Ademe
- Carte :  Google maps
- Proxy : Zenrows


## Run the Code

Pour faire fonctionner Localimmo en local, les étapes à suivre :

### Pré-requis

- Serveur PHP (Apache recommandé)
- Python (pour les scripts d’analyse)
- Composer (si nécessaire)
- Accès à un fichier `.htaccess` pour stocker les clés API

### Clés API

Avant de lancer le projet, assurez-vous d’avoir :

- Une **clé Google Maps API**
- Une **clé ZenRows API**

Ces clés doivent être stockées dans le fichier `.htaccess` à la racine du projet :

```apache
SetEnv GOOGLE_API_KEY API_KEY_HERE
SetEnv ZENROWS_API_KEY API_KEY_HERE
```

