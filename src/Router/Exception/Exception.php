<?php declare(strict_types=1);

namespace Framework312\Router\Exception;

/**
 * Exception de base du framework : le code de l'exception EST le code HTTP à renvoyer.
 * (HttpMethodNotImplemented et InvalidViewImplementation du boilerplate en héritent déjà.)
 */
class Exception extends \Exception {
    /** $status = code HTTP envoyé au client ; $previous = erreur d'origine éventuelle. */
    public function __construct(string $message = '', int $status = 500, ?\Throwable $previous = null) {
        parent::__construct($message, $status, $previous);
    }

    public function getStatusCode(): int {
        return $this->getCode();
    }
}

?>
