<?php declare(strict_types=1);

namespace Framework312\Template;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/** Moteur de templates basé sur Twig. */
class TwigRenderer implements Renderer {
    private FilesystemLoader $loader;
    private Environment $twig;
    private string $base;

    /** $templates_path : dossier racine des templates, accessible dans Twig avec @global. */
    public function __construct(string $templates_path) {
        $this->base = rtrim($templates_path, '/\\');
        $this->loader = new FilesystemLoader($this->base);
        $this->loader->addPath($this->base, 'global'); // @global/header.twig
        $this->twig = new Environment($this->loader);
    }

    /** $data = tableau de variables pour le template ; $template = nom du fichier (ex. "book.twig"). */
    public function render(mixed $data, string $template): string {
        return $this->twig->render($template, (array) $data);
    }

    /** Le tag est un sous-dossier de /templates : on y cherche en priorité + alias @Tag. */
    public function register(string $tag) {
        $dir = $this->base . '/' . str_replace('\\', '/', $tag);
        if (!is_dir($dir)) {
            // Pas de dossier dédié à cette vue : on utilise seulement le dossier racine.
            return;
        }
        $this->loader->prependPath($dir);
        $this->loader->addPath($dir, $tag);
    }
}

?>
