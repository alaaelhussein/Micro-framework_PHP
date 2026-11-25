<?php declare(strict_types=1);

namespace Framework312\Router;

/** Contrat d'un routeur : associer des chemins à des vues, puis répondre à la requête. */
interface Router {
    /** $path : "/book/:id" ; $class_or_view : nom de la classe ou instance d'une vue. */
    public function register(string $path, string|object $class_or_view);
    /** Traite la requête HTTP courante et envoie la réponse. */
    public function serve(mixed ...$args);
}

?>
