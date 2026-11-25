<?php declare(strict_types=1);

namespace Framework312\Template;

/**
 * Contrat d'un moteur de templates.
 * Le routeur et les vues ne dépendent que de cette interface : on peut remplacer Twig.
 */
interface Renderer {
    /** Retourne le texte produit par le template $template avec les données $data. */
    public function render(mixed $data, string $template): string;
    /** Déclare un groupe de templates (le nom de la classe de la vue). */
    public function register(string $tag);
}

?>
