# Changelog

Toutes les modifications notables de ce projet sont documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/).

## [Non publié]

### Ajouté
- Tests unitaires pour `RatingHandler` (calcul de la moyenne, répartition des notes par valeur).
- Tests unitaires pour `VideoGameVoter` (droit de poster un avis selon l'utilisateur et ses avis existants).
- Tests fonctionnels pour la soumission d'un avis (`ReviewTest`) : dépôt d'un avis, blocage d'un second avis, restriction aux utilisateurs connectés.
- Fichier `CHANGELOG.md`.

### Corrigé
- Configuration de l'environnement de développement (`.env.local`, `.env.test.local`) alignée sur PostgreSQL (via Docker) au lieu de MySQL/SQLite, pour correspondre aux migrations existantes.
- Ajout du dossier `.idea/` au `.gitignore`.

## [0.1.0] - 2024-04-22

### Ajouté
- Initialisation du projet Symfony (CritiPixel).
- Listing et notation des jeux vidéo.
- Authentification (inscription / connexion).
- Filtrage et tri de la liste des jeux vidéo.
- Tests fonctionnels initiaux : connexion, inscription, filtrage, affichage d'un jeu vidéo.

