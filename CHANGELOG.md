# Changelog

Toutes les modifications notables de ce projet sont documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/).

## [Non publié]

### Ajouté
- `TagFixtures` : jeu de données réaliste de tags (genres de jeux vidéo : Action, RPG, Stratégie, etc.).
- Association aléatoire de 1 à 4 tags par jeu vidéo dans `VideoGameFixtures`.
- Génération d'avis (`Review`) réalistes dans `VideoGameFixtures` : note (1 à 5), commentaire optionnel, entre 0 et 5 avis par jeu, un seul avis par utilisateur et par jeu.
- Calcul cohérent de la moyenne (`averageRating`) et de la répartition des notes (`numberOfRatingsPerValue`) à partir des avis générés, via les services `CalculateAverageRating`/`CountRatingsPerValue` désormais utilisés dans les fixtures.
- Les jeux vidéo d'index 2, 3 et 4 sont volontairement exclus de la génération d'avis aléatoires afin de ne pas perturber les assertions des tests fonctionnels existants (`ReviewTest`).
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



