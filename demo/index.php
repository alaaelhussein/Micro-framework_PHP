<?php
// Lancer :  php -S localhost:8000 demo/index.php
//   http://localhost:8000/book/7ABd9x   (GET)      curl -X POST http://localhost:8000/book/7ABd9x   (POST)
//   http://localhost:8000/api/books/42  (JSON)     http://localhost:8000/hello  (HTML)
require __DIR__ . '/../vendor/autoload.php'; // Composer

use Framework312\Router\View\{TemplateView, JSONView, HTMLView};
use Framework312\Router\{Request, SimpleRouter};
use Framework312\Template\TwigRenderer;

/** Page avec template Twig : demo/templates/Book/book.twig (dossier = nom de la classe). */
class Book extends TemplateView
{
    protected function get(Request $request): array
    {
        return ['template' => 'book.twig', 'data' => ['id' => $request->attributes->get('id'), 'method' => 'GET']];
    }
    protected function post(Request $request): array
    {
        return ['template' => 'book.twig', 'data' => ['id' => $request->attributes->get('id'), 'method' => 'POST']];
    }
}
/** Réponse JSON : le tableau retourné est encodé automatiquement. */
class BookApi extends JSONView
{
    protected function get(Request $request): array
    {
        return ['id' => $request->attributes->get('id'), 'title' => 'PHP orienté objet'];
    }
}

/** Réponse HTML : la chaîne retournée est envoyée telle quelle. */
class Home extends TemplateView
{
    protected function get(Request $request): array
    {
        return ['template' => 'home.twig', 'data' => []];
    }
}


/** Réponse HTML : la chaîne retournée est envoyée telle quelle. */
class Hello extends HTMLView
{
    protected function get(Request $request): string
    {
        return '<h1>Hello Framework312</h1>';
    }
}

// Création du routeur, puis déclaration des routes (":id" = partie variable de l'URL).
$router = new SimpleRouter(new TwigRenderer(__DIR__ . '/templates/'));
$router->register('/', Home::class);
$router->register('/book/:id', 'Book');
$router->register('/api/books/:id', BookApi::class);
$router->register('/hello', 'Hello');
// Traite la requête courante.
$router->serve();
