<?php declare(strict_types=1);

namespace Framework312\Router\View;

use Framework312\Router\Request;
use Symfony\Component\HttpFoundation\Response;

/** Vue qui renvoie du JSON : le résultat du verbe est transformé avec json_encode. */
abstract class JSONView extends BaseView {
    /** Pas de moteur de templates pour du JSON. */
    public static function use_template(): bool {
        return false;
    }

    public function render(Request $request): Response {
        $result = $this->call_verb($request);
        if ($result instanceof Response) {
            return $result; // le verbe a construit sa propre réponse (201, redirection...)
        }
        $json = json_encode($result, JSON_THROW_ON_ERROR);
        return new Response($json, Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }
}

?>
