<?php declare(strict_types=1);

namespace Framework312\Router\View;

use Framework312\Router\Request;
use Symfony\Component\HttpFoundation\Response;

/** Vue qui renvoie du HTML : le verbe (get, post...) retourne directement le texte HTML. */
abstract class HTMLView extends BaseView {
    /** Pas de moteur de templates : le HTML est fourni par le verbe. */
    public static function use_template(): bool {
        return false;
    }

    public function render(Request $request): Response {
        $result = $this->call_verb($request);
        if ($result instanceof Response) {
            return $result; // le verbe a construit sa propre réponse (201, redirection...)
        }
        return new Response((string) $result, Response::HTTP_OK, ['Content-Type' => 'text/html']);
    }
}

?>
