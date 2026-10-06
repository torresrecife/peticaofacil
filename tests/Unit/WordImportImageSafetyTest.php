<?php

namespace Tests\Unit;

use App\Services\WordImportService;
use PHPUnit\Framework\TestCase;

class WordImportImageSafetyTest extends TestCase
{
    private $service;
    private $assetsDir;
    private $outsideDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Subclasse so para alcancar os metodos protegidos que fazem a inlinizacao.
        $this->service = new class extends WordImportService {
            public function resolve($src, $dir)
            {
                return $this->resolveImagePath($src, $dir);
            }

            public function inline($html, $dir)
            {
                libxml_use_internal_errors(true);
                $dom = new \DOMDocument('1.0', 'UTF-8');
                $dom->loadHTML('<?xml encoding="UTF-8"><body>' . $html . '</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                libxml_clear_errors();
                $body = $dom->getElementsByTagName('body')->item(0);
                $this->inlineLocalImages($body, $dir);

                $out = '';
                foreach ($body->childNodes as $child) {
                    $out .= $dom->saveHTML($child);
                }

                return $out;
            }
        };

        $base = sys_get_temp_dir() . '/word-img-' . uniqid('', true);
        $this->assetsDir = $base . '/assets';
        $this->outsideDir = $base . '/outside';
        mkdir($this->assetsDir . '/sub', 0777, true);
        mkdir($this->outsideDir, 0777, true);

        file_put_contents($this->assetsDir . '/logo.png', $this->pngBytes());
        file_put_contents($this->assetsDir . '/sub/foto.png', $this->pngBytes());
        file_put_contents($this->outsideDir . '/segredo.env', 'DB_PASSWORD=segredo-do-servidor');
    }

    protected function tearDown(): void
    {
        foreach ([$this->assetsDir . '/sub/foto.png', $this->assetsDir . '/logo.png', $this->outsideDir . '/segredo.env'] as $file) {
            @unlink($file);
        }
        foreach ([$this->assetsDir . '/sub', $this->assetsDir, $this->outsideDir, dirname($this->assetsDir)] as $dir) {
            @rmdir($dir);
        }

        parent::tearDown();
    }

    public function test_image_extracted_next_to_the_document_is_accepted()
    {
        $this->assertSame($this->norm(realpath($this->assetsDir . '/logo.png')), $this->norm($this->service->resolve('logo.png', $this->assetsDir)));
        $this->assertSame($this->norm(realpath($this->assetsDir . '/sub/foto.png')), $this->norm($this->service->resolve('sub/foto.png', $this->assetsDir)));
        $this->assertSame($this->norm(realpath($this->assetsDir . '/logo.png')), $this->norm($this->service->resolve('sub/../logo.png', $this->assetsDir)));
    }

    /**
     * @dataProvider maliciousSources
     */
    public function test_sources_pointing_outside_the_conversion_folder_are_refused($src)
    {
        $this->assertNull($this->service->resolve($src, $this->assetsDir));
    }

    public function maliciousSources()
    {
        return [
            'file absoluto' => ['file:///' . ltrim(str_replace('\\', '/', __DIR__), '/') . '/../../outside/segredo.env'],
            'file do env' => ['file://' . '/etc/passwd'],
            'escapa com ..' => ['../outside/segredo.env'],
            'escapa com .. codificado' => ['..%2Foutside%2Fsegredo.env'],
            'caminho absoluto unix' => ['/etc/passwd'],
            'caminho absoluto windows' => ['C:\\\\Windows\\\\win.ini'],
            'http' => ['http://exemplo.com/x.png'],
            'vazio' => [''],
        ];
    }

    public function test_inline_keeps_the_real_image_and_strips_a_linked_server_file()
    {
        $secret = 'file:///' . ltrim(str_replace('\\', '/', $this->outsideDir), '/') . '/segredo.env';
        $html = '<p><img src="logo.png"></p><p><img src="' . htmlspecialchars($secret, ENT_QUOTES) . '"></p>';

        $result = $this->service->inline($html, $this->assetsDir);

        $this->assertStringContainsString('data:image/png;base64,', $result);
        $this->assertStringNotContainsString('segredo-do-servidor', $result);
        $this->assertStringNotContainsString('file://', $result);
        // As duas tags continuam no HTML: a legitima embutida, a do ataque sem src.
        $this->assertSame(1, preg_match_all('/data:image\/png;base64,/', $result));
        $this->assertSame(2, substr_count($result, '<img'));
        $this->assertSame(1, preg_match('/<img(?![^>]*\bsrc=)/', $result));
    }

    public function test_a_non_image_file_inside_the_folder_is_not_embedded()
    {
        file_put_contents($this->assetsDir . '/nota.txt', 'conteudo qualquer');
        try {
            $result = $this->service->inline('<img src="nota.txt">', $this->assetsDir);
            $this->assertStringNotContainsString('conteudo qualquer', $result);
            $this->assertStringNotContainsString('data:text', $result);
        } finally {
            @unlink($this->assetsDir . '/nota.txt');
        }
    }

    private function norm($path)
    {
        return $path === null ? null : str_replace('\\', '/', $path);
    }

    private function pngBytes()
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAC0lEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
    }
}
