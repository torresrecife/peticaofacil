<?php

namespace Tests\Unit;

use App\Support\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

class HtmlSanitizerTest extends TestCase
{
    const PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAC0lEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    public function test_scripts_styles_and_frames_are_removed_with_their_content()
    {
        $r = HtmlSanitizer::clean('<p>Oi</p><script>alert(1)</script><style>body{}</style><iframe src="http://x"></iframe>');

        $this->assertStringNotContainsString('<script', $r);
        $this->assertStringNotContainsString('alert(1)', $r);
        $this->assertStringNotContainsString('<style', $r);
        $this->assertStringNotContainsString('<iframe', $r);
        $this->assertStringContainsString('<p>Oi</p>', $r);
    }

    public function test_event_handlers_are_stripped_but_the_element_stays()
    {
        $r = HtmlSanitizer::clean('<img src="logo.png" onerror="alert(1)" onclick="x()">');

        $this->assertStringContainsString('<img', $r);
        $this->assertStringNotContainsStringIgnoringCase('onerror', $r);
        $this->assertStringNotContainsStringIgnoringCase('onclick', $r);
    }

    /**
     * @dataProvider dangerousUrls
     */
    public function test_dangerous_link_schemes_are_removed($href)
    {
        $r = HtmlSanitizer::clean('<a href="' . $href . '">texto</a>');

        $this->assertStringContainsString('>texto</a>', $r);
        $this->assertNotRegExp('/href\s*=/i', $r);
    }

    public function dangerousUrls()
    {
        return [
            'javascript' => ['javascript:alert(1)'],
            'maiusculas' => ['JaVaScRiPt:alert(1)'],
            'com tab por entidade' => ['java&#9;script:alert(1)'],
            'vbscript' => ['vbscript:msgbox(1)'],
            'data html' => ['data:text/html,<script>alert(1)</script>'],
            'espaco antes' => ['  javascript:alert(1)'],
        ];
    }

    public function test_safe_links_are_kept()
    {
        $r = HtmlSanitizer::clean('<a href="https://tribunal.jus.br/p?x=1">t</a>');
        $this->assertStringContainsString('href="https://tribunal.jus.br/p?x=1"', $r);

        $r = HtmlSanitizer::clean('<a href="#ancora">t</a><a href="mailto:a@b.com">m</a>');
        $this->assertStringContainsString('href="#ancora"', $r);
        $this->assertStringContainsString('href="mailto:a@b.com"', $r);
    }

    public function test_embedded_images_and_table_formatting_are_preserved()
    {
        $html = '<table style="border-collapse:collapse"><tr>'
            . '<td align="center" width="50%"><b>A</b></td><td><img src="' . self::PNG . '" style="width:1px"></td>'
            . '</tr></table>';

        $r = HtmlSanitizer::clean($html);

        $this->assertStringContainsString('<table', $r);
        $this->assertStringContainsString('border-collapse:collapse', $r);
        $this->assertStringContainsString('align="center"', $r);
        $this->assertStringContainsString('width="50%"', $r);
        $this->assertStringContainsString('data:image/png;base64,', $r);
        $this->assertStringContainsString('<b>A</b>', $r);
    }

    public function test_dangerous_styles_are_dropped_safe_styles_survive()
    {
        $r = HtmlSanitizer::clean('<p style="color:red;background:url(javascript:alert(1))">t</p>');
        $this->assertStringNotContainsStringIgnoringCase('javascript', $r);
        $this->assertStringContainsString('<p', $r);
        $this->assertStringContainsString('>t</p>', $r);

        $r = HtmlSanitizer::clean('<span style="color:#b42318;font-weight:700">x</span>');
        $this->assertStringContainsString('color:#b42318', $r);
        $this->assertStringContainsString('font-weight:700', $r);
    }

    public function test_forms_comments_and_svg_are_removed()
    {
        $r = HtmlSanitizer::clean('<form action="http://evil"><input name="x"></form><!--[if IE]><script>alert(1)</script><![endif]--><svg><script>alert(1)</script></svg><p>fim</p>');

        $this->assertStringNotContainsStringIgnoringCase('<form', $r);
        $this->assertStringNotContainsStringIgnoringCase('<input', $r);
        $this->assertStringNotContainsString('<!--', $r);
        $this->assertStringNotContainsStringIgnoringCase('<svg', $r);
        $this->assertStringNotContainsString('alert(1)', $r);
        $this->assertStringContainsString('<p>fim</p>', $r);
    }

    public function test_plain_text_and_accents_survive_and_empty_stays_empty()
    {
        $this->assertSame('', HtmlSanitizer::clean(''));
        $this->assertSame('   ', HtmlSanitizer::clean('   '));

        $r = HtmlSanitizer::clean('<p>ação e coração — citação "x"</p>');
        $this->assertStringContainsString('ação e coração', $r);
        $this->assertStringContainsString('citação', $r);
    }
}
