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
