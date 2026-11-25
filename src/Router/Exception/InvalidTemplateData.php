<?php declare(strict_types=1);

namespace Framework312\Router\Exception;

use Symfony\Component\HttpFoundation\Response;

/** 500 : le verbe d'une TemplateView n'a pas retourné un nom de template et ses données. */
class InvalidTemplateData extends Exception {
    public function __construct(string $classname) {
        parent::__construct("A verb method of {$classname} must return ['template' => string, 'data' => array] (or [string, array]) to be rendered by a TemplateView.", Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

?>
