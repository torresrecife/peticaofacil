<?php

namespace Tests\Unit;

use App\Support\WordHtmlCleaner;
use PHPUnit\Framework\TestCase;

class WordHtmlCleanerTest extends TestCase
{
    public function test_footnotes_keep_the_mark_in_the_text_and_go_to_the_end()
    {
        $html = '<p>Golpe do boleto<a class="sdfootnoteanc" name="sdfootnote2anc" href="#sdfootnote2sym"><sup>2</sup></a>; e outro'
            . '<a class="sdfootnoteanc" name="sdfootnote1anc" href="#sdfootnote1sym"><sup>1</sup></a>.</p>'
            . '<div id="sdfootnote1"><p class="sdfootnote-western"><a class="sdfootnotesym" name="sdfootnote1sym" href="#sdfootnote1anc">1</a> Nota A</p><p class="sdfootnote-western"><br/></p></div>'
            . '<div id="sdfootnote2"><p class="sdfootnote-western"><a class="sdfootnotesym" href="#sdfootnote2anc">2</a><font size="1" style="font-size: 8pt"> Nota B</font></p></div>';

        $out = str_replace(["\r", "\n"], "", WordHtmlCleaner::clean($html)); // saveHTML quebra linha entre blocos

        // Numeracao pela ordem das chamadas no texto.
        $this->assertStringContainsString('<p>Golpe do boleto<sup>1</sup>; e outro<sup>2</sup>.</p>', $out);
        $this->assertStringContainsString('<div class="peticao-notas"><hr>', $out);
        $this->assertRegExp('/<p style="font-size: 9pt; text-align: justify; overflow-wrap: anywhere"><sup>1<\/sup> <span style="font-size: 8pt">Nota B<\/span><\/p>/', $out);
        $this->assertRegExp('/<p style="font-size: 9pt; text-align: justify; overflow-wrap: anywhere"><sup>2<\/sup> Nota A<\/p>/', $out);
        $this->assertStringNotContainsString('sdfootnote', $out);
    }

    public function test_word_markup_is_simplified_and_runs_are_joined()
    {
        $html = '<div title="header"><p>E-mail do escritorio</p></div>'
            . '<h1 class="western" align="center" style="text-align: left; orphans: 0; widows: 0; font-weight: bold">'
            . '<font face="Arial, sans-serif"><font size="2" style="font-size: 10pt">EXCELENTISSIMO</font></font>'
            . '<font face="Arial, sans-serif"><font size="2" style="font-size: 10pt"><span style="letter-spacing: 1.2pt"> </span></font></font>'
            . '<font face="Arial, sans-serif"><font size="2" style="font-size: 10pt">SENHOR</font></font></h1>'
            . '<p lang="pt-PT" style="color: #000000"><b>BANCO X</b><b> </b><b>S/A</b></p>';

        $out = str_replace(["\r", "\n"], "", WordHtmlCleaner::clean($html)); // saveHTML quebra linha entre blocos

        $this->assertSame(
            '<p style="text-align: center; font-weight: bold"><span style="font-size: 10pt">EXCELENTISSIMO SENHOR</span></p>'
            . '<p><b>BANCO X S/A</b></p>',
            $out
        );
    }

    public function test_lists_have_no_browser_margin_and_word_internal_fonts_and_trailing_blank_lines_go_away()
    {
        $html = '<ol start="2"><li><p>Item</p></li></ol>'
            . '<p><span style="font-family: TimesNewRomanNormal, sans-serif; font-size: 10pt">55496080</span>'
            . '<span style="font-family: Times New Roman, serif">citacao</span></p>'
            . '<p>Assinatura</p><p><br></p><p>&nbsp;</p>'
            . '<div id="sdfootnote1"><p><a class="sdfootnotesym" href="#x">1</a> Nota</p></div>';

        $out = str_replace(["\r", "\n"], '', WordHtmlCleaner::clean($html));

        // Sem os rotulos do Word, o numero vem do inicio da lista.
        $this->assertStringContainsString('<p style="margin-left: 0.75cm; text-indent: -0.75cm"><span style="display: inline-block; min-width: 0.75cm; text-indent: 0">2.</span>Item</p>', $out);
        $this->assertStringContainsString('<span style="font-size: 10pt">55496080</span>', $out);
        $this->assertStringContainsString('<span style="font-family: Times New Roman, serif">citacao</span>', $out);
        $this->assertStringContainsString('<p>Assinatura</p><div class="peticao-notas">', $out);
    }

    public function test_multilevel_word_list_keeps_only_the_word_label_of_each_item()
    {
        // Como o LibreOffice exporta "a)" e "d.1)": niveis vazios antes do item.
        $html = '<ol start="3"><li><p><b>DOS PEDIDOS.</b></p></li></ol>'
            . '<ol><li><ol start="3"><li><ol type="a"><li><p>O deferimento da liminar</p></li></ol></li></ol></li></ol>'
            . '<ol><li><ol start="3"><li><ol start="4" type="a"><li><p>A citação da parte ré:</p></li></ol></li></ol></li></ol>'
            . '<ol><li><ol start="3"><li><ol type="a"><li><ol><li><p>no prazo de 05 dias</p></li></ol></li></ol></li></ol></li></ol>';
        $labels = \App\Services\WordImportService::parseListLabels(
            "        3. DOS PEDIDOS.\n            a) O deferimento da liminar\n            d) A citação da parte ré:\n                d.1) no prazo de 05 dias\n"
        );

        $out = str_replace(["\r", "\n"], '', WordHtmlCleaner::clean($html, $labels));

        $this->assertStringNotContainsString('<ol', $out);
        $label = function ($text) {
            return '<span style="display: inline-block; min-width: 0.75cm; text-indent: 0">' . $text . '</span>';
        };
        $this->assertStringContainsString('<p style="margin-left: 0.75cm; text-indent: -0.75cm">' . $label('3.') . '<b>DOS PEDIDOS.</b></p>', $out);
        $this->assertStringContainsString('<p style="margin-left: 1.38cm; text-indent: -0.75cm">' . $label('a)') . 'O deferimento da liminar</p>', $out);
        $this->assertStringContainsString('<p style="margin-left: 1.38cm; text-indent: -0.75cm">' . $label('d)') . 'A citação da parte ré:</p>', $out);
        $this->assertStringContainsString('<p style="margin-left: 2.01cm; text-indent: -0.75cm">' . $label('d.1)') . 'no prazo de 05 dias</p>', $out);
    }

    public function test_html_codes_typed_as_text_in_word_become_characters()
    {
        // No Word o texto era literalmente: DJe 20/10/2023: &quot;RECURSO ESPECIAL&quot;
        $out = WordHtmlCleaner::clean('<p>DJe 20/10/2023: &amp;quot;RECURSO ESPECIAL&amp;quot; &amp; outros</p>');

        $this->assertSame('<p>DJe 20/10/2023: "RECURSO ESPECIAL" &amp; outros</p>', $out);
    }

    public function test_text_boxes_become_bordered_blocks_without_absolute_position()
    {
        $html = '<p style="text-indent: 1.25cm"><a></a><span style="position: absolute; top: 0.26cm; left: 12cm; width: 222px">'
            . '<img src="data:image/gif;base64,AAAA" alt="Forme1" hspace="286331153"><br clear="left"></span>'
            . '<span id="Cadre1" style="position: absolute; top: 0cm; left: 0cm; width: 5.88cm"><p><b>Tema 1132</b></p></span><br></p>'
            . '<center><table width="475"><tr><td>MARCA</td></tr></table></center>';

        $out = str_replace(["\r", "\n"], "", WordHtmlCleaner::clean($html)); // saveHTML quebra linha entre blocos

        $this->assertStringNotContainsString('absolute', $out);
        $this->assertStringNotContainsString('<img', $out);
        $this->assertStringContainsString('<div style="border: 1px solid #000000; padding: 2pt 6pt; margin: 0 0 6pt auto; width: 5.88cm"><p><b>Tema 1132</b></p></div><p style="text-indent: 1.25cm">', $out);
        $this->assertStringContainsString('<div style="text-align: center"><table width="475" style="margin-left: auto; margin-right: auto">', $out);
    }
}
