<?php declare(strict_types=1);

namespace Framework312\Router;

use Framework312\Router\Exception as RouterException;
use Framework312\Template\Renderer;
use Symfony\Component\HttpFoundation\Response;

/**
 * Une vue enregistrée : vérifie sa classe, puis l'instancie à chaque requête.
 * Définie ici car elle n'est utilisée que par SimpleRouter.
 */
class Route {
    private const VIEW_CLASS = 'Framework312\Router\View\BaseView';
    private const VIEW_USE_TEMPLATE_FUNC = 'use_template';
    private const VIEW_RENDER_FUNC = 'render';

    private string $view;

    /** Si on reçoit une instance, seule sa classe est conservée. */
    public function __construct(string|object $class_or_view) {
        $reflect = new \ReflectionClass($class_or_view);
        $view = $reflect->getName();
        if (!$reflect->isSubclassOf(self::VIEW_CLASS)) {
            throw new RouterException\InvalidViewImplementation($view);
        }
        $this->view = $view;
    }

    /** Crée la vue (avec le moteur de templates si elle en a besoin) et renvoie sa réponse. */
    public function call(Request $request, ?Renderer $engine): Response {
        $view = $this->view;
        if ($view::use_template()) {
            if ($engine === null) {
                throw new RouterException\Exception("View {$view} needs a Renderer", Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $instance = new $view($engine);
        } else {
            $instance = new $view();
        }
        return $instance->{self::VIEW_RENDER_FUNC}($request);
    }
}

/**
 * Routeur du framework.
 * Parcours d'une requête : serve() -> handle() -> match() -> Route::call() -> render() de la vue.
 */
class SimpleRouter implements Router {
    private Renderer $engine;
    /** @var array<int, array{regex: string, params: string[], route: Route}> */
    private array $routes = [];

    public function __construct(Renderer $engine) {
        $this->engine = $engine;
    }

    /** "/book/:id" -> regex "#^/book/(?P<id>[^/]+)$#" ; les segments ":nom" sont les catch-all. */
    public function register(string $path, string|object $class_or_view) {
        $route = new Route($class_or_view); // lève InvalidViewImplementation tout de suite si la vue est invalide
        $params = [];
        $parts = array_map(function (string $segment) use (&$params): string {
            if (str_starts_with($segment, ':') && \strlen($segment) > 1) {
                $params[] = substr($segment, 1);
                return '(?P<' . substr($segment, 1) . '>[^/]+)';
            }
            return preg_quote($segment, '#');
        }, explode('/', trim($path, '/')));
        $regex = '#^/' . implode('/', $parts) . '/?$#';
        $this->routes[] = ['regex' => $regex, 'params' => $params, 'route' => $route];
    }

    /** Construit la Response pour une Request (sans l'envoyer : pratique pour les tests). */
    public function handle(Request $request): Response {
        try {
            $path = $request->getPathInfo();
            $match = $this->match($path);
            if ($match === null) {
                throw new RouterException\NotFound($path);
            }
            [$route, $values] = $match;
            foreach ($values as $name => $value) {
                $request->attributes->set($name, rawurldecode($value));
            }
            return $route->call($request, $this->engine);
        } catch (RouterException\Exception $e) {
            return new Response($e->getMessage(), $e->getStatusCode(), ['Content-Type' => 'text/plain']);
        } catch (\Throwable $e) {
            // erreur imprévue (Twig, JSON...) : détail dans les logs, message générique pour le client
            error_log((string) $e);
            return new Response('Internal Server Error', Response::HTTP_INTERNAL_SERVER_ERROR, ['Content-Type' => 'text/plain']);
        }
    }

    /** Point d'entrée du site (appelé dans index.php) : lit la requête et envoie la réponse. */
    public function serve(mixed ...$args): void {
        $request = new Request(...$args);
        $this->handle($request)->prepare($request)->send(); // prepare() retire le corps pour HEAD
    }

    /** @return array{0: Route, 1: array<string, string>}|null  la route la plus précise (moins de catch-all) */
    private function match(string $path): ?array {
        $best = null;
        foreach ($this->routes as $entry) {
            if (preg_match($entry['regex'], $path, $m) !== 1) {
                continue;
            }
            if ($best === null || \count($entry['params']) < \count($best['params'])) {
                $best = $entry + ['values' => array_intersect_key($m, array_flip($entry['params']))];
            }
        }
        return $best === null ? null : [$best['route'], $best['values']];
    }
}

?>
