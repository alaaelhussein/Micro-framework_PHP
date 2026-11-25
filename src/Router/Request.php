<?php declare(strict_types=1);

namespace Framework312\Router;

use Symfony\Component\HttpFoundation\{Request as SymfonyRequest, InputBag};

/**
 * Requête HTTP : celle de Symfony, avec en plus :
 * - la lecture des formulaires envoyés en PUT, PATCH et DELETE ;
 * - un « contexte » : les valeurs passées à serve(), lues avec fromContext().
 */
class Request extends SymfonyRequest {
    private array $context = [];

    public function __construct(mixed ...$args) {
        parent::__construct($_GET, $_POST, [], $_COOKIE, $_FILES, $_SERVER);

        $content_type = $this->headers->get('CONTENT_TYPE', '');
        $method = strtoupper($this->server->get('REQUEST_METHOD', 'GET'));

        // PHP ne remplit $_POST que pour POST : pour les autres verbes, on lit le corps nous-mêmes.
        $is_urlencoded = str_starts_with($content_type, 'application/x-www-form-urlencoded');
        $modification_methods = ['PUT', 'DELETE', 'PATCH'];
        if ($is_urlencoded && \in_array($method, $modification_methods, true)) {
            parse_str($this->getContent(), $data);
            $this->request = new InputBag($data);
        }

        // On ne garde que les valeurs de type string.
        if (func_num_args() > 0) {
            $args = array_filter($args, 'is_string');
        }
        // clés insensibles à la casse, mais valeurs conservées telles quelles (id == 7ABd9x)
        $this->context = array_change_key_case($args, CASE_LOWER);
    }

    /** Valeur du contexte (nom insensible à la casse), ou null si elle n'existe pas. */
    public function fromContext(string $key): mixed {
        return $this->context[strtolower($key)] ?? null;
    }
}

?>
