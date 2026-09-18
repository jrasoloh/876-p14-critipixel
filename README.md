<img src="assets/images/logo.png" alt="CritiPixel" width="200" />

# CritiPixel

## Pré-requis
* PHP >= 8.2
* Composer
* Extension PHP Xdebug
* Symfony (binaire)

## Installation

### Composer
Dans un premier temps, installer les dépendances :
```bash
composer install
```

### Docker (optionnel)
Si vous souhaitez utiliser Docker Compose, il vous suffit de lancer la commande suivante :
```bash
docker compose up -d
```

## Configuration

### Base de données
Actuellement, le fichier `.env` est configuré pour la base de données PostgreSQL mise en place dans `docker-compose.yml`.
Cependant, vous pouvez créer un fichier `.env.local` si nécessaire pour configurer l'accès à la base de données.
Exemple :
```dotenv
DATABASE_URL=mysql://root:Password123!@host:3306/criti-pixel
```

### PHP (optionnel)
Vous pouvez surcharger la configuration PHP en créant un fichier `php.local.ini`.

La version de PHP à utiliser est épinglée à la racine du projet via le fichier
[`.php-version`](.php-version) (actuellement **8.3**). Le binaire Symfony et
[`phpenv`](https://github.com/phpenv/phpenv) / [Herd](https://herd.laravel.com/)
le lisent automatiquement.

> ⚠️ Les dépendances Symfony 6.4 utilisées ici (`twig/twig`, `vich/uploader-bundle`,
> etc.) ne sont pas entièrement compatibles PHP 8.4 : plusieurs `Deprecated` sont
> émis à l'exécution, ce qui casse la session (`session_id(): headers already sent`)
> et provoque un 500. **Restez sur PHP 8.2 ou 8.3** tant que ces libs n'ont pas
> publié de release compatible 8.4.

## Usage

### Base de données

#### Supprimer la base de données
```bash
symfony console doctrine:database:drop --force --if-exists
```

#### Créer la base de données
```bash
symfony console doctrine:database:create
```

#### Exécuter les migrations
```bash
symfony console doctrine:migrations:migrate -n
```

#### Charger les fixtures
```bash
symfony console doctrine:fixtures:load -n --purge-with-truncate
```

*Note : Vous pouvez exécuter ces commandes avec l'option `--env=test` pour les exécuter dans l'environnement de test.*

### SASS

#### Compiler les fichiers SASS
```bash
symfony console sass:build
```
*Note : le fichier `.symfony.local.yaml` est configuré pour surveiller les fichiers SASS et les compiler automatiquement quand vous lancez le serveur web de Symfony.*

### Tests
```bash
symfony php bin/phpunit
```

*Note : Penser à charger les fixtures avant chaque éxécution des tests.*

### Qualité du code

Le projet utilise deux outils **complémentaires** d'analyse de la qualité du code :

| Outil                                                | Rôle                                                          | Configuration            |
| ---------------------------------------------------- | ------------------------------------------------------------- | ------------------------ |
| [PHPStan](https://phpstan.org/)                      | Analyse statique du typage et détection de bugs potentiels    | `phpstan.dist.neon`      |
| [PHP CS Fixer](https://cs.symfony.com/)              | Formatage / respect des standards de style (`@Symfony`, PSR-12) | `.php-cs-fixer.dist.php` |

Les deux outils sont exécutés au niveau de rigueur suivant :
- **PHPStan** : niveau **6** (typage strict des `array`, génériques, etc.) avec les extensions Symfony, Doctrine et PHPUnit.
- **PHP CS Fixer** : règles `@Symfony`, `@PSR12`, `declare_strict_types`.

#### Analyse statique (PHPStan)
```bash
composer phpstan
```
*Note : PHPStan a besoin du container Symfony compilé. Si vous obtenez une erreur `containerXmlPath`, lancez `symfony console cache:clear` au préalable.*

#### Style de code (PHP CS Fixer)
```bash
# Vérification (dry-run) : liste les fichiers à corriger sans les modifier.
composer cs-check

# Correction automatique.
composer cs-fix
```

### Intégration continue (GitHub Actions)

Le workflow `.github/workflows/ci.yml` est déclenché à chaque `push` et `pull_request` sur `main`. Il exécute deux jobs en parallèle :

| Job       | Étapes                                                                                             |
| --------- | -------------------------------------------------------------------------------------------------- |
| `quality` | `composer install` (avec cache) → `composer phpstan` → `composer cs-check`                         |
| `tests`   | Démarrage d'un service PostgreSQL 16, création de la base de test, migrations, fixtures, `bin/phpunit` |

L'environnement est reproductible : PHP 8.2 (avec `ctype`, `iconv`, `intl`, `mbstring`, `pgsql`) installé via [`shivammathur/setup-php`](https://github.com/shivammathur/setup-php), et les dépendances Composer sont mises en cache d'un run à l'autre. Les runs obsolètes d'une même branche sont automatiquement annulés (`concurrency` + `cancel-in-progress`).

Pour reproduire la CI en local :
```bash
composer phpstan
composer cs-check
composer test
```

### Serveur web
```bash
symfony serve
```