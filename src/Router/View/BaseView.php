<?php declare(strict_types=1);

namespace Framework312\Router\View;

use Framework312\Router\Exception as RouterException;
use Framework312\Router\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Classe mère de toutes les vues.
 * Une vue redéfinit les verbes qu'elle accepte (get, post...) ; les autres répondent 405.
 */
abstract class BaseView {
    protected function get(Request $request): mixed {
        throw new RouterException\HttpMethodNotImplemented(static::class, 'GET');
    }

    protected function post(Request $request): mixed {
        throw new RouterException\HttpMethodNotImplemented(static::class, 'POST');
    }

    protected function patch(Request $request): mixed {
        throw new RouterException\HttpMethodNotImplemented(static::class, 'PATCH');
    }

    protected function put(Request $request): mixed {
        throw new RouterException\HttpMethodNotImplemented(static::class, 'PUT');
    }

    protected function delete(Request $request): mixed {
        throw new RouterException\HttpMethodNotImplemented(static::class, 'DELETE');
    }

    /** Appelle la méthode de verbe (get, post...) correspondant à la méthode HTTP de la requête. */
    protected function call_verb(Request $request): mixed {
        return match ($request->getMethod()) {
            // HEAD = GET sans corps : le corps est retiré dans SimpleRouter::serve().
            'GET', 'HEAD' => $this->get($request),
            'POST' => $this->post($request),
            'PATCH' => $this->patch($request),
            'PUT' => $this->put($request),
            'DELETE' => $this->delete($request),
            default => throw new RouterException\HttpMethodNotImplemented(static::class, $request->getMethod()),
        };
    }

    /** true si la vue doit recevoir un moteur de templates (Renderer) à sa création. */
    abstract static public function use_template(): bool;
    /** Transforme le résultat du verbe en réponse HTTP. */
    abstract public function render(Request $request): Response;
}

?>
