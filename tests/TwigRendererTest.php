<?php declare(strict_types=1);

namespace Tests;

use Framework312\Template\TwigRenderer;
use PHPUnit\Framework\TestCase;

/** Teste TwigRenderer avec de vrais fichiers, dans un dossier temporaire. */
class TwigRendererTest extends TestCase {
    private string $dir;

    /** Avant chaque test : crée un layout global et deux templates dans Book/. */
    protected function setUp(): void {
        $this->dir = sys_get_temp_dir() . '/fw312_' . uniqid();
        mkdir($this->dir . '/Book', 0777, true);
        file_put_contents($this->dir . '/layout.twig', '[{% block b %}{% endblock %}]');
        file_put_contents($this->dir . '/Book/page.twig', '{% extends "@global/layout.twig" %}{% block b %}Hello {{ name }}{% endblock %}');
        file_put_contents($this->dir . '/Book/inc.twig', '{% include "@Book/page.twig" %}');
    }

    /** Après chaque test : supprime les fichiers temporaires. */
    protected function tearDown(): void {
        foreach (['/Book/page.twig', '/Book/inc.twig', '/layout.twig'] as $f) { @unlink($this->dir . $f); }
        @rmdir($this->dir . '/Book'); @rmdir($this->dir);
    }

    public function testRenderUsesTagFolderAndGlobalAlias(): void {
        $e = new TwigRenderer($this->dir . '/');
        $e->register('Book');
        $this->assertSame('[Hello Ada]', $e->render(['name' => 'Ada'], 'page.twig')); // "page.twig" -> /Book/page.twig
        $this->assertSame('[Hello Bob]', $e->render(['name' => 'Bob'], 'inc.twig'));  // include @Book/...
    }

    public function testRegisterUnknownTagDoesNotFail(): void {
        $e = new TwigRenderer($this->dir);
        $e->register('Nope');
        $this->assertSame('[]', $e->render([], 'layout.twig'));
    }
}

?>
