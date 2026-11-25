<?php declare(strict_types=1);

namespace Framework312\Router\Exception;

use Symfony\Component\HttpFoundation\Response;

/** 405 : la route existe, mais la vue ne gère pas ce verbe HTTP. */
class HttpMethodNotImplemented extends Exception {
    public function __construct(string $classname, string $method) {
        parent::__construct("Method {$method} not implemented for class {$classname}", Response::HTTP_METHOD_NOT_ALLOWED);
    }
}

?>
