<?php

declare(strict_types=1);

namespace Framework312\Router\View;

use Framework312\Router\Exception\InvalidTemplateData;
use Framework312\Router\Request;
use Framework312\Template\Renderer;
use Symfony\Component\HttpFoundation\Response;

/**
 * Vue qui passe par un moteur de templates.
 * Le verbe retourne ['template' => 'book.twig', 'data' => [...]] (ou [ 'book.twig', [...] ]).
 */
abstract class TemplateView extends BaseView
{
    public function __construct(protected Renderer $engine)
    {
        // static::class = classe réellement instanciée (late static binding) : "Book", pas "TemplateView"
        $this->engine->register(static::class);
    }

    /** Demande au routeur de fournir un Renderer au constructeur. */
    public static function use_template(): bool
    {
        return true;
    }

    public function render(Request $request): Response
    {
        $result = $this->call_verb($request);
        if ($result instanceof Response) {
            return $result; // le verbe a construit sa propre réponse (201, redirection...)
        }
        if (!\is_array($result)) {
            throw new InvalidTemplateData(static::class);
        }
        // Accepte les deux formes : clés "template"/"data" ou positions 0/1.
        $template = $result['template'] ?? $result[0] ?? null;
        $data = $result['data'] ?? $result[1] ?? [];
        if (
            !\is_string($template)
            || trim($template) === ''
            || !\is_array($data)
        ) {
            throw new InvalidTemplateData(static::class);
        }
        return new Response($this->engine->render($data, $template), Response::HTTP_OK);
    }
}
