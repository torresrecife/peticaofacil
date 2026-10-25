<?php

namespace Tests\Unit;

use App\Support\PeticaoTipografia;
use PHPUnit\Framework\TestCase;

class PeticaoTipografiaTest extends TestCase
{
    public function test_px_of_the_legacy_models_becomes_pt_from_10_up()
    {
        $html = '<p style="text-align:justify"><span style="font-size:10px"><span style="font-family:Tahoma">texto</span></span>'
            . '<span style="font-size: 12.5px; color:red">titulo</span><span style="font-size:9px">contato</span></p>';

        $this->assertSame(
            '<p style="text-align:justify"><span style="font-size:10pt"><span style="font-family:Tahoma">texto</span></span>'
            . '<span style="font-size:12.5pt; color:red">titulo</span><span style="font-size:9px">contato</span></p>',
            PeticaoTipografia::pxToPt($html)
        );
    }

    public function test_pt_and_text_outside_style_are_untouched()
    {
        $html = '<p style="font-size:11pt">O valor de 10px no texto continua</p>';

        $this->assertSame($html, PeticaoTipografia::pxToPt($html));
    }

    public function test_legacy_header_padding_is_removed_and_text_cell_is_right_aligned()
    {
        $nbsp = "\xC2\xA0";
        $html = "<table border=\"0\"><tbody><tr><td><p><img src=\"/logo.jpg\" /></p></td><td>\n<p><br />\n"
            . str_repeat("$nbsp ", 20) . "<span style=\"font-size:9px\">Rua Djalma Farias, CEP: 52.030-195<br />\n"
            . str_repeat("&nbsp; ", 30) . "Fone: 55 (81) 3222.2159</span></p></td></tr></tbody></table>"
            . "<p>&nbsp; &nbsp; Corpo com recuo</p>";

        $result = PeticaoTipografia::trimRightAlignedPadding($html);

        $this->assertStringContainsString('<p style="text-align:right"><br />' . "\n" . '<span style="font-size:9px">Rua Djalma', $result);
        $this->assertStringContainsString("<br />\nFone: 55", $result);
        $this->assertStringContainsString('<p><img src="/logo.jpg" /></p>', $result);
        // Fora do cabecalho, o recuo com &nbsp; do corpo fica como esta.
        $this->assertStringContainsString('<p>&nbsp; &nbsp; Corpo com recuo</p>', $result);
    }

    public function test_right_aligned_block_loses_only_leading_padding()
    {
        $html = '<p style="text-align:right">&nbsp;&nbsp;&nbsp;Recife, 24 de outubro de 2026.<br />&nbsp;&nbsp;Assinatura&nbsp;&nbsp;final</p>';

        $this->assertSame(
            '<p style="text-align:right">Recife, 24 de outubro de 2026.<br />Assinatura&nbsp;&nbsp;final</p>',
            PeticaoTipografia::trimRightAlignedPadding($html)
        );
    }
}
