<?php

namespace App\Support;

/**
 * Tipografia das pecas, igual a do sistema antigo.
 *
 * Os modelos do legado gravam o tamanho da letra em "px" (ex.: Tahoma 10px).
 * O html2pdf do legado imprimia o px no tamanho real (12px = 9pt), e por isso
 * os usuarios aumentavam a letra das pecas para 11-12px no editor. Decisao
 * (Fabio, 2026-10-25): aqui o numero vale em pt (10px -> 10pt), a proporcao
 * que o usuario via no editor, sem precisar alterar os modelos. Peticoes
 * longas podem ganhar 1-2 paginas em relacao ao PDF do legado.
 *
 * Paragrafos sem margem e entrelinha de 115%, como o PDF do legado
 * (`p{margin:0px;line-height:115%}` em inc/getpdf.php): o espaco entre
 * paragrafos vem das linhas em branco que o proprio modelo ja traz.
 */
class PeticaoTipografia
{
    /**
     * Letra do texto sem tamanho definido (101 dos 217 modelos ativos), em pt.
     * O legado usava 11pt (`p{font-size:11pt}` no editor e no PDF).
     */
    const BASE_FONT_PT = '11';

    /** Entrelinha do corpo da peca. */
    const LINE_HEIGHT = '1.15';

    /** Mesma entrelinha em porcentagem (Word). */
    const LINE_HEIGHT_PERCENT = '115%';

    /**
     * Em bloco alinhado a direita, os &nbsp; no comeco de cada linha so
     * empurravam o texto no editor do legado; na pagina A4 fazem a linha
     * estourar e quebrar (ex.: "CEP: 52.030-" / "195" no cabecalho antigo).
     * Remove esses espacos so no inicio das linhas desses blocos.
     */
    public static function trimRightAlignedPadding($html)
    {
        // O saneador (HtmlSanitizer) devolve o &nbsp; ja como caractere.
        if (!is_string($html) || (stripos($html, 'nbsp') === false && strpos($html, "\xC2\xA0") === false)) {
            return $html;
        }

        // Blocos alinhados a direita.
        $html = preg_replace_callback('/<(p|div|td)\b([^>]*text-align\s*:\s*right[^>]*)>(.*?)<\/\1>/is', function ($matches) {
            return '<' . $matches[1] . $matches[2] . '>' . self::trimLineStarts($matches[3]) . '</' . $matches[1] . '>';
        }, $html);

        // Cabecalho antigo embutido no inicio da peca: tabela com o logotipo,
        // cujo texto de contato era empurrado para a direita com &nbsp;.
        // A celula do logotipo fica como esta; nas de texto, sem os &nbsp;, o
        // alinhamento a direita que eles imitavam passa a ser explicito.
        return preg_replace_callback('/^(\s*)(<table\b[^>]*>)(.*?<img\b.*?)(<\/table>)/is', function ($matches) {
            $cells = preg_replace_callback('/(<td\b[^>]*>)(.*?)(<\/td>)/is', function ($cell) {
                if (stripos($cell[2], '<img') !== false) {
                    return $cell[0];
                }
                $content = self::trimLineStarts($cell[2]);
                $content = preg_replace_callback('/<p\b([^>]*)>/i', function ($p) {
                    if (stripos($p[1], 'text-align') !== false) {
                        return $p[0];
                    }
                    if (preg_match('/style\s*=\s*(["\'])(.*?)\1/i', $p[1], $style)) {
                        return '<p' . str_replace($style[0], 'style=' . $style[1] . 'text-align:right;' . $style[2] . $style[1], $p[1]) . '>';
                    }

                    return '<p' . $p[1] . ' style="text-align:right">';
                }, $content);

                return $cell[1] . $content . $cell[3];
            }, $matches[3]);

            return $matches[1] . $matches[2] . $cells . $matches[4];
        }, $html, 1);
    }

    /**
     * Tira os &nbsp; do inicio de cada linha (inicio do trecho, de cada <p> e
     * depois de cada <br>), passando por cima de tags de abertura como <span>.
     */
    protected static function trimLineStarts($html)
    {
        $padding = '(?:&nbsp;|&#160;|\xC2\xA0|\s)+';
        $openTags = '(?:\s*<(?!br\b|\/)[a-z][^>]*>)*';

        $html = preg_replace('/^(' . $openTags . ')' . $padding . '/i', '$1', $html);
        $html = preg_replace('/(<p\b[^>]*>' . $openTags . ')' . $padding . '(?=\S)/i', '$1', $html);
        $html = preg_replace('/(<br\s*\/?>\s*' . $openTags . ')' . $padding . '/i', '$1', $html);

        return $html;
    }

    /**
     * A partir deste tamanho o px vira pt. Abaixo dele ficam os textos miudos
     * (contato do cabecalho em 9px, alinhado com &nbsp;): crescendo, quebram.
     */
    const MIN_PX_TO_CONVERT = 10;

    /**
     * font-size em px -> o mesmo numero em pt, so dentro de style="".
     */
    public static function pxToPt($html)
    {
        if (!is_string($html) || $html === '' || stripos($html, 'px') === false) {
            return $html;
        }

        return preg_replace_callback('/style\s*=\s*(["\'])(.*?)\1/is', function ($matches) {
            $style = preg_replace_callback('/font-size\s*:\s*(\d+(?:\.\d+)?)\s*px/i', function ($size) {
                if ((float) $size[1] < self::MIN_PX_TO_CONVERT) {
                    return $size[0];
                }

                return 'font-size:' . $size[1] . 'pt';
            }, $matches[2]);

            return 'style=' . $matches[1] . $style . $matches[1];
        }, $html);
    }
}
