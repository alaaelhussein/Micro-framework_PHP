<?php

declare(strict_types=1);

namespace Framework312\Router;

use Framework312\Router\Exception as RouterException;
use Framework312\Template\Renderer;
use Symfony\Component\HttpFoundation\Response;
use Framework312\Router\View\BaseView;

/**
 * Une vue enregistrée : vérifie sa classe, puis l'instancie à chaque requête.
 * Utilisée par SimpleRouter.
 */
class Route
{
    private const VIEW_CLASS = BaseView::class;


    private string $view;

    /** Si on reçoit une instance, seule sa classe est conservée. */
    public function __construct(string|object $class_or_view)
    {
        try {
            $reflect = new \ReflectionClass($class_or_view);
        } catch (\ReflectionException $e) {
            throw new RouterException\InvalidViewImplementation((string) $class_or_view);
        }
        $view = $reflect->getName();
        if (!$reflect->isSubclassOf(self::VIEW_CLASS) || !$reflect->isInstantiable()) {
            throw new RouterException\InvalidViewImplementation($view);
        }
        $this->view = $view;
    }

    /** Crée la vue (avec le moteur de templates si elle en a besoin) et renvoie sa réponse. */
    public function call(Request $request, ?Renderer $engine): Response
    {
        $view = $this->view;
        if ($view::use_template()) {
            if ($engine === null) {
                throw new RouterException\Exception("View {$view} needs a Renderer", Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $instance = new $view($engine);
        } else {
            $instance = new $view();
        }
        return $instance->render($request);
    }
}
