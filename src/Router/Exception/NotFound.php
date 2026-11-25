<?php declare(strict_types=1);

namespace Framework312\Router\Exception;

use Symfony\Component\HttpFoundation\Response;

/** 404 : aucune route enregistrée ne correspond au chemin demandé. */
class NotFound extends Exception {
    public function __construct(string $path) {
        parent::__construct("No view registered for path {$path}", Response::HTTP_NOT_FOUND);
    }
}

?>
