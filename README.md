# Framework312

Micro-framework PHP réalisé en groupe pour l'UE L312. Il associe des URL à des vues, qui renvoient du HTML, du JSON ou une page Twig.

## Prérequis

- PHP 8.2 ou plus récent
- [Composer](https://getcomposer.org/)

## Installation

Toutes les commandes se lancent depuis ce dossier (celui qui contient `composer.json`).

```
composer install
```

Le dossier `vendor/` est créé par cette commande. Il ne doit pas être ajouté au dépôt Git.

## Lancer la démo

```
php -S localhost:8080 demo/index.php
```

| Requête | Résultat |
|---|---|
| `GET` http://localhost:8080/ | Page d'accueil |
| `GET` http://localhost:8080/hello | Page HTML |
| `GET` ou `POST` http://localhost:8080/book/7ABd9x | Page Twig |
| `GET` http://localhost:8080/api/books/42 | JSON |


Pour tester le `POST` (sous PowerShell, écrire `curl.exe`) :

```
curl.exe -X POST http://localhost:8080/book/7ABd9x
```

## Lancer les tests

```
php vendor/bin/phpunit tests
```

## Organisation du projet

```
src/
  Router/
    Router.php         interface du routeur
    SimpleRouter.php   routeur : trouve la vue qui correspond à l'URL
    Route.php    validation et instanciation des vues
    Request.php        requête HTTP (étend celle de Symfony)
    Exception/         erreurs du framework, avec leur code HTTP
    View/              BaseView, HTMLView, JSONView, TemplateView
  Template/
    Renderer.php       interface d'un moteur de templates
    TwigRenderer.php   implémentation avec Twig
demo/                  site d'exemple et ses templates
tests/                 tests PHPUnit
```

## Parcours d'une requête

1. `SimpleRouter::serve()` crée la `Request`.
2. `handle()` cherche la route qui correspond au chemin. Si plusieurs conviennent, celle qui a le moins de parties variables est choisie.
3. Les parties variables de l'URL sont placées dans `$request->attributes`.
4. La vue est créée et son `render()` appelle la méthode du verbe HTTP (`get`, `post`, `put`, `patch` ou `delete`).
5. La réponse est envoyée au navigateur.

## Ajouter une page

Créer une vue, puis l'enregistrer dans `demo/index.php` avant `$router->serve()` :

```php
class Bonjour extends HTMLView {
    protected function get(Request $request): string {
        $nom = htmlspecialchars($request->attributes->get('nom'));
        return "<h1>Bonjour $nom</h1>";
    }
}

$router->register('/bonjour/:nom', Bonjour::class);
```

`:nom` est une partie variable de l'URL.

| Classe à étendre | Ce que retourne la méthode du verbe |
|---|---|
| `HTMLView` | une chaîne HTML |
| `JSONView` | une valeur convertie en JSON |
| `TemplateView` | `['template' => 'page.twig', 'data' => [...]]` |

Une méthode peut aussi retourner directement une `Response` de Symfony, par exemple pour choisir le code HTTP.

### Templates Twig

Les templates d'une vue se placent dans un dossier qui porte le nom de sa classe : `demo/templates/Book/` pour la vue `Book`. Le dossier `templates/` est accessible dans Twig avec `@global` :

```twig
{% extends "@global/layout.twig" %}
```

## Erreurs

| Code | Cause |
|---|---|
| 404 | aucune route ne correspond au chemin |
| 405 | la vue ne gère pas ce verbe HTTP |
| 500 | données de template invalides ou erreur imprévue |

Pour une erreur imprévue, le navigateur affiche seulement « Internal Server Error ». Le détail est écrit dans les logs PHP.

Enregistrer une classe qui n'hérite pas de `BaseView` lève une exception dès l'appel à `register()`.
