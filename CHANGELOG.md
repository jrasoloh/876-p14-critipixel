# Changelog

Toutes les modifications notables de ce projet sont documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/).

## [Non publié]

### Ajouté
- **Analyse statique** : installation et configuration de [`PHPStan`](https://phpstan.org/) au niveau 6, avec ses extensions officielles Symfony, Doctrine et PHPUnit pour une compréhension fine du framework.
  - Configuration dans `phpstan.dist.neon` (paths `src/` + `tests/`, chargement du container Symfony compilé, chargement de l'`ObjectManager` Doctrine via `tests/object-manager.php`).
  - Nouveau script `composer phpstan`.
- **Style de code** : installation et configuration de [`PHP CS Fixer`](https://cs.symfony.com/) comme outil **complémentaire** à PHPStan (l'un vérifie les types et la logique, l'autre le formatage / PSR-12).
  - Configuration dans `.php-cs-fixer.dist.php` avec les règles `@Symfony`, `@PSR12` et `declare_strict_types`.
  - Nouveaux scripts `composer cs-check` (dry-run) et `composer cs-fix` (auto-fix).
- Documentation dans le `README.md` de l'utilisation des deux outils.
- Test fonctionnel `FilterTest` complété pour couvrir le filtrage des jeux vidéo par tags :
  - Filtrage par un seul tag, par plusieurs tags (sémantique ET : le jeu doit posséder tous les tags demandés) et sans aucun tag, via un `@dataProvider`.
  - Cas d'un tag inexistant : le filtre est ignoré et la liste complète est retournée sans erreur.
- Assignation **déterministe** de 3 tags réservés (`Action`, `Aventure`, `RPG`) dans `VideoGameFixtures`, exclus du tirage aléatoire, afin de garantir des comptes exacts et reproductibles pour les tests de filtrage.
- Installation et configuration de [`DAMADoctrineTestBundle`](https://github.com/dmaicher/doctrine-test-bundle) : chaque test fonctionnel s'exécute désormais dans une transaction isolée automatiquement annulée à la fin du test (plus besoin de relancer les fixtures entre deux exécutions de la suite).
- Test fonctionnel `ReviewTest` complété pour couvrir l'ensemble du cycle d'ajout d'un avis :
  - Cas nominal : ajout d'un avis (avec et sans commentaire), redirection 302, persistance en base vérifiée, formulaire masqué après un premier avis.
  - Erreurs de validation (réponse 422) : note manquante, note hors des choix valides, commentaire trop long.
  - Autorisations : formulaire invisible pour les visiteurs anonymes, soumission directe anonyme rejetée, tentative de contournement (double avis via un jeton CSRF réutilisé) rejetée avec un code 403.
- Validation `#[NotBlank]` sur `Review::$rating` et `#[Length(max: 2000)]` sur `Review::$comment`, avec migration associée (colonne `rating` rendue nullable en base pour permettre la validation avant persistance).
- `TagFixtures` : jeu de données réaliste de tags (genres de jeux vidéo : Action, RPG, Stratégie, etc.).
- Association aléatoire de 1 à 4 tags par jeu vidéo dans `VideoGameFixtures`.
- Génération d'avis (`Review`) réalistes dans `VideoGameFixtures` : note (1 à 5), commentaire optionnel, entre 0 et 5 avis par jeu, un seul avis par utilisateur et par jeu.
- Calcul cohérent de la moyenne (`averageRating`) et de la répartition des notes (`numberOfRatingsPerValue`) à partir des avis générés, via les services `CalculateAverageRating`/`CountRatingsPerValue` désormais utilisés dans les fixtures.
- Les jeux vidéo d'index 2 à 8 sont volontairement exclus de la génération d'avis aléatoires afin de ne pas perturber les assertions des tests fonctionnels existants (`ReviewTest`).
- Tests unitaires dédiés `CalculateAverageRatingTest` et `CountRatingsPerValueTest` pour `RatingHandler` (calcul de la moyenne des notes et répartition des notes par valeur, sur plusieurs jeux vidéo avec des scénarios de notation variés).
- Tests unitaires pour `VideoGameVoter` (droit de poster un avis selon l'utilisateur et ses avis existants).
- Fichier `CHANGELOG.md`.

### Corrigé
- Correction des **44 erreurs** remontées par PHPStan niveau 6 sur l'ensemble du projet, notamment :
  - Génériques manquants sur les classes étendant `AbstractType`, `Voter`, et les `Collection` Doctrine (`VideoGame::$tags`, `VideoGame::$reviews`).
  - Incohérence de type Doctrine sur `VideoGame::$releaseDate` (`DateTimeInterface` → `DateTimeImmutable`) et `VideoGame::$updatedAt` (rendue `?DateTimeImmutable` conformément au schéma nullable).
  - Types itérables non spécifiés (`array<...>`, `iterable<...>`) sur les getters/setters, les data providers et les paramètres de méthodes de test.
  - Correction de `Pagination::getIterator()` (annotation `@return Traversable<int, Page>` au lieu de `Traversable<string, int>`).
  - Contrôleur `VideoGameController::show()` : ajout d'une assertion `$user instanceof User` pour typer correctement l'argument passé à `Review::setUser()`.
  - Correction du type générique du helper `array_fill_callback()` (`@template T of array` → `@template T`).
  - Remplacement de `array_walk([$manager, 'persist'])` par des `foreach` explicites dans les fixtures (plus lisible et sans faux positif sur la signature de `array_walk`).
- Application automatique des règles PHP CS Fixer sur **43 fichiers** (imports non utilisés, formatage PHPDoc, etc.).
- **Bug critique** : soumettre le formulaire d'avis sans sélectionner de note provoquait une erreur 500 (le setter `Review::setRating()` refusait `null`) au lieu d'un message de validation. La colonne et la propriété sont maintenant nullables, avec une validation applicative appropriée.
- **Bug de test** : `RegisterTest::getFormData()` utilisait `[valeurs par défaut] + $overrideData`, mais l'opérateur `+` de PHP conserve la valeur de gauche en cas de clé dupliquée : les données de substitution n'étaient donc jamais appliquées. Ce bug était masqué par l'absence d'isolation entre les tests (corrigée ci-dessus par DAMADoctrineTestBundle).
- Configuration de l'environnement de développement (`.env.local`, `.env.test.local`) alignée sur PostgreSQL (via Docker) au lieu de MySQL/SQLite, pour correspondre aux migrations existantes.
- Ajout du dossier `.idea/` au `.gitignore`.

## [0.1.0] - 2024-04-22

### Ajouté
- Initialisation du projet Symfony (CritiPixel).
- Listing et notation des jeux vidéo.
- Authentification (inscription / connexion).
- Filtrage et tri de la liste des jeux vidéo.
- Tests fonctionnels initiaux : connexion, inscription, filtrage, affichage d'un jeu vidéo.









