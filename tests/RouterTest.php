<?php declare(strict_types=1);

namespace Tests;

use Framework312\Router\{Request, SimpleRouter};
use Framework312\Router\Exception\InvalidViewImplementation;
use Framework312\Router\View\{HTMLView, JSONView, TemplateView};
use Framework312\Template\Renderer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/** Faux moteur : prouve que le Router/les vues ne dépendent que de l'interface Renderer (injection de dépendances). */
class MockRenderer implements Renderer {
    public array $registered = [];
    public function render(mixed $data, string $template): string { return $template . ':' . json_encode($data); }
    public function register(string $tag) { $this->registered[] = $tag; }
}

/** Petites vues utilisées uniquement par les tests. */
class PageView extends HTMLView {
    protected function get(Request $request): string { return '<p>id=' . $request->attributes->get('id') . '</p>'; }
}
class ApiView extends JSONView {
    protected function get(Request $request): array { return ['id' => $request->attributes->get('id')]; }
    protected function post(Request $request): array { return ['posted' => $request->request->get('name')]; }
}
class TplView extends TemplateView {
    protected function get(Request $request): array { return ['template' => 'x.twig', 'data' => ['id' => $request->attributes->get('id')]]; }
    protected function post(Request $request): array { return ['bad' => 1]; }
}
/** N'hérite pas de BaseView : doit être refusée par register(). */
class NotAView {}
class HtmlResponseView extends HTMLView {
    protected function get(Request $request): Response { return new Response('créé', 201); }
}
class JsonResponseView extends JSONView {
    protected function get(Request $request): Response { return new Response('créé', 201); }
}
class TplResponseView extends TemplateView {
    protected function get(Request $request): Response { return new Response('créé', 201); }
}
class BadJsonView extends JSONView {
    protected function get(Request $request): float { return NAN; } // json_encode refuse NAN
}
/** Moteur en panne : simule une erreur Twig imprévue. */
class FailingRenderer extends MockRenderer {
    public function render(mixed $data, string $template): string { throw new \RuntimeException('détail secret'); }
}

class RouterTest extends TestCase {
    /** Simule une requête HTTP en remplissant les variables globales lues par Request. */
    private function request(string $method, string $uri, array $post = []): Request {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;
        $_POST = $post;
        return new Request();
    }

    /** Routeur avec les routes communes aux tests. */
    private function router(?MockRenderer $r = null): SimpleRouter {
        $router = new SimpleRouter($r ?? new MockRenderer());
        $router->register('/page/:id', PageView::class);
        $router->register('/api/:id', 'Tests\ApiView');
        $router->register('/tpl/:id', new TplView(new MockRenderer()));
        $router->register('/api/special', PageView::class);
        return $router;
    }

    public function testCatchAllValueGoesInAttributes(): void {
        $res = $this->router()->handle($this->request('GET', '/page/7ABd9x?x=1'));
        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('<p>id=7ABd9x</p>', $res->getContent());
        $this->assertStringContainsString('text/html', $res->headers->get('Content-Type'));
    }

    public function testJsonView(): void {
        $res = $this->router()->handle($this->request('GET', '/api/42'));
        $this->assertSame('{"id":"42"}', $res->getContent());
        $this->assertSame('application/json', $res->headers->get('Content-Type'));
        $res = $this->router()->handle($this->request('POST', '/api/42', ['name' => 'Bob']));
        $this->assertSame('{"posted":"Bob"}', $res->getContent());
    }

    public function testTemplateViewRegistersTagAndRenders(): void {
        $engine = new MockRenderer();
        $res = $this->router($engine)->handle($this->request('GET', '/tpl/9'));
        $this->assertSame('x.twig:{"id":"9"}', $res->getContent());
        $this->assertSame(['Tests\TplView'], $engine->registered);
    }

    public function testExactPathWinsOverCatchAll(): void {
        $res = $this->router()->handle($this->request('GET', '/api/special'));
        $this->assertSame('<p>id=</p>', $res->getContent()); // PageView, pas ApiView
    }

    public function testUnknownPathIs404(): void {
        $this->assertSame(404, $this->router()->handle($this->request('GET', '/nope'))->getStatusCode());
        $this->assertSame(404, $this->router()->handle($this->request('GET', '/page/1/extra'))->getStatusCode());
    }

    public function testMethodNotImplementedIs405(): void {
        $this->assertSame(405, $this->router()->handle($this->request('DELETE', '/page/1'))->getStatusCode());
        $this->assertSame(405, $this->router()->handle($this->request('OPTIONS', '/page/1'))->getStatusCode());
    }

    public function testHeadBehavesLikeGet(): void {
        $this->assertSame(200, $this->router()->handle($this->request('HEAD', '/page/1'))->getStatusCode());
    }

    public function testVerbCanReturnItsOwnResponse(): void {
        $router = new SimpleRouter(new MockRenderer());
        foreach ([HtmlResponseView::class, JsonResponseView::class, TplResponseView::class] as $i => $view) {
            $router->register("/r$i", $view);
            $res = $router->handle($this->request('GET', "/r$i"));
            $this->assertSame(201, $res->getStatusCode());
            $this->assertSame('créé', $res->getContent());
        }
    }

    public function testUnexpectedErrorIs500WithoutDetails(): void {
        $router = new SimpleRouter(new FailingRenderer());
        $router->register('/json', BadJsonView::class);
        $router->register('/tpl', TplView::class);
        // On masque les logs : ces erreurs sont volontaires.
        $log = ini_set('error_log', PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null');
        try {
            foreach (['/json', '/tpl'] as $path) {
                $res = $router->handle($this->request('GET', $path));
                $this->assertSame(500, $res->getStatusCode());
                $this->assertSame('Internal Server Error', $res->getContent());
            }
        } finally {
            ini_set('error_log', (string) $log);
        }
    }

    public function testBadTemplateDataIs500(): void {
        $this->assertSame(500, $this->router()->handle($this->request('POST', '/tpl/1'))->getStatusCode());
    }

    public function testRegisterRejectsNonView(): void {
        $this->expectException(InvalidViewImplementation::class);
        (new SimpleRouter(new MockRenderer()))->register('/x', NotAView::class);
    }

    public function testRequestKeepsContextValues(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $r = new Request(Id: '7ABd9x');
        $this->assertSame('7ABd9x', $r->fromContext('ID'));
    }

    public function testPutUrlencodedBodyIsParsed(): void {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
        $_POST = [];
        $r = new class extends Request { public function getContent(bool $asResource = false): string { return 'a=1&b=2'; } };
        $this->assertSame('1', $r->request->get('a'));
        unset($_SERVER['CONTENT_TYPE']);
    }
}

?>
