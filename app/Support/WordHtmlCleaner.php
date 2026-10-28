<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Arruma o HTML que o LibreOffice/PhpWord geram a partir do Word.
 *
 * - Notas de rodape: a chamada fica no texto como <sup>N</sup> e o texto das
 *   notas vai para o fim da peca, numa lista "peticao-notas" (letra 9pt).
 * - Cabecalho/rodape do Word saem: o sistema aplica o cabecalho do escritorio.
 * - <font> vira <span style>, titulos (h1..h6) viram paragrafo, align vira
 *   text-align, e saem atributos e estilos sem efeito (lang, classes, orphans,
 *   Arial/Calibri, cor preta...).
 * - Spans vizinhos com o mesmo estilo viram um so. O Word quebra o texto em
 *   pedacos por palavra; juntos, "EXCELENTISSIMO SENHOR" fica num trecho so e
 *   a troca do texto original pelo campo (importacao com IA) encontra a frase.
 */
class WordHtmlCleaner
{
    /** Estilos que nao mudam nada na peca (ou sao o padrao do sistema). */
    const DROP_PROPERTIES = [
        'orphans', 'widows', 'so-language', 'direction', 'letter-spacing',
        'page', 'page-break-before', 'page-break-after', 'page-break-inside',
        'position', 'top', 'left', 'z-index', 'mso-', '-qt-',
    ];

    /** Fontes que equivalem a padrao do sistema (Arial). */
    const DEFAULT_FONTS = ['arial', 'calibri', 'helvetica', 'sans-serif', 'arial narrow'];

    /** Fontes diferentes da padrao que o importador mantem. */
    const KNOWN_FONTS = ['times new roman', 'times', 'georgia', 'garamond', 'book antiqua', 'cambria', 'courier new', 'verdana', 'tahoma', 'trebuchet ms', 'segoe ui', 'century gothic', 'palatino linotype'];

    /** Nota justificada; endereco web longo quebra em qualquer ponto (sem abrir buraco na linha). */
    const NOTE_STYLE = 'font-size: 9pt; text-align: justify; overflow-wrap: anywhere';

    /** Recuo de cada nivel de lista e espaco do rotulo ("a)", "d.1)"), em cm. */
    const LIST_INDENT_CM = 0.63;
    const LIST_LABEL_CM = 0.75;

    /**
     * @param string $html
     * @param array  $listLabels rotulos dos itens de lista na ordem do Word
     *                           ([['label' => 'd.1)', 'text' => 'no prazo de 05...'], ...]),
     *                           tirados da conversao do DOCX para texto.
     */
    public static function clean($html, array $listLabels = [])
    {
        $html = (string) $html;
        if (trim($html) === '') {
            return $html;
        }

        libxml_use_internal_errors(true);
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML('<?xml encoding="UTF-8"><body>' . $html . '</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        $body = $dom->getElementsByTagName('body')->item(0);
        if (!$body) {
            return $html;
        }

        $xpath = new DOMXPath($dom);
        static::removeWordHeaderFooter($xpath);
        static::removeTrailingEmptyParagraphs($body);
        static::moveFootnotes($dom, $xpath, $body);
        static::convertLists($dom, $xpath, $listLabels);
        static::convertElements($dom, $xpath);
        static::cleanAttributes($xpath);

        // Junta spans ate nao haver mais o que juntar.
        for ($round = 0; $round < 6; $round++) {
            $changed = static::unwrapBareSpans($xpath);
            $changed = static::flattenNestedSpans($xpath) || $changed;
            $body->normalize();
            $changed = static::mergeSiblingSpans($xpath) || $changed;
            $body->normalize();
            if (!$changed) {
                break;
            }
        }

        $out = '';
        foreach ($body->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        return trim($out);
    }

    /**
     * Linhas em branco depois do ultimo texto (antes das notas do LibreOffice)
     * so empurravam as notas para uma pagina a mais.
     */
    protected static function removeTrailingEmptyParagraphs(DOMElement $body)
    {
        $node = $body->lastChild;
        while ($node) {
            $previous = $node->previousSibling;
            $isNote = $node instanceof DOMElement && strpos($node->getAttribute('id'), 'sdfootnote') === 0;
            $empty = $node->nodeType === XML_TEXT_NODE
                ? trim(str_replace("\xC2\xA0", ' ', $node->nodeValue)) === ''
                : ($node instanceof DOMElement
                    && in_array(strtolower($node->nodeName), ['p', 'div', 'br'], true)
                    && trim(str_replace("\xC2\xA0", ' ', $node->textContent)) === ''
                    && $node->getElementsByTagName('img')->length === 0
                    && $node->getElementsByTagName('table')->length === 0);
            if (!$isNote && !$empty) {
                break;
            }
            if (!$isNote) {
                $body->removeChild($node);
            }
            $node = $previous;
        }
    }

    /**
     * So a conversao das listas, para corrigir um modelo ja importado sem
     * mexer no resto do texto (campos, edicoes feitas depois).
     */
    public static function convertListsInHtml($html, array $listLabels = [])
    {
        $html = (string) $html;
        if (!preg_match('/<(ol|ul)\b/i', $html)) {
            return $html;
        }
        libxml_use_internal_errors(true);
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML('<?xml encoding="UTF-8"><body>' . $html . '</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        $body = $dom->getElementsByTagName('body')->item(0);
        if (!$body) {
            return $html;
        }
        static::convertLists($dom, new DOMXPath($dom), $listLabels);
        $out = '';
        foreach ($body->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        return trim($out);
    }

    /** Cabecalho e rodape nativos do Word (LibreOffice: <div title="header">). */
    protected static function removeWordHeaderFooter(DOMXPath $xpath)
    {
        foreach (iterator_to_array($xpath->query('//div[@title="header" or @title="footer"]')) as $node) {
            $node->parentNode->removeChild($node);
        }
    }

    /**
     * LibreOffice: chamada <a class="sdfootnoteanc" href="#sdfootnoteNsym"><sup>N</sup></a>
     * e texto em <div id="sdfootnoteN">. PhpWord: <a class="NoteRef"><sup>N</sup></a>.
     * A numeracao segue a ordem das chamadas no texto.
     */
    protected static function moveFootnotes(DOMDocument $dom, DOMXPath $xpath, DOMElement $body)
    {
        $bodies = [];
        foreach (iterator_to_array($xpath->query('//div[starts-with(@id, "sdfootnote")]')) as $div) {
            if (preg_match('/^sdfootnote(\d+)$/', $div->getAttribute('id'), $m)) {
                $bodies[$m[1]] = $div;
                $div->parentNode->removeChild($div);
            }
        }

        $numbers = [];
        $anchors = $xpath->query('//a[contains(concat(" ", normalize-space(@class), " "), " sdfootnoteanc ") or contains(concat(" ", normalize-space(@class), " "), " NoteRef ")]');
        foreach (iterator_to_array($anchors) as $anchor) {
            $key = preg_match('/(\d+)/', $anchor->getAttribute('href') ?: $anchor->getAttribute('name'), $m) ? $m[1] : (string) (count($numbers) + 1);
            if (!isset($numbers[$key])) {
                $numbers[$key] = count($numbers) + 1;
            }
            $sup = $dom->createElement('sup', (string) $numbers[$key]);
            $anchor->parentNode->replaceChild($sup, $anchor);
        }

        if (!$bodies) {
            return;
        }

        $container = $dom->createElement('div');
        $container->setAttribute('class', 'peticao-notas');
        $container->appendChild($dom->createElement('hr'));
        $next = count($numbers);
        $ordered = [];
        foreach ($bodies as $key => $div) {
            $ordered[isset($numbers[$key]) ? $numbers[$key] : ++$next] = $div;
        }
        ksort($ordered);
        foreach ($ordered as $number => $div) {
            foreach ($xpath->query('.//a[contains(@class, "sdfootnotesym")]', $div) as $symbol) {
                $symbol->parentNode->removeChild($symbol);
            }
            $first = true;
            foreach (iterator_to_array($div->childNodes) as $child) {
                if (!$child instanceof DOMElement || trim(str_replace("\xC2\xA0", ' ', $child->textContent)) === '') {
                    continue;
                }
                $paragraph = $dom->createElement('p');
                $paragraph->setAttribute('style', self::NOTE_STYLE);
                if ($first) {
                    $paragraph->appendChild($dom->createElement('sup', (string) $number));
                    $first = false;
                }
                $source = in_array(strtolower($child->nodeName), ['p', 'div'], true) ? $child : null;
                if ($source) {
                    foreach (iterator_to_array($source->childNodes) as $grandChild) {
                        $paragraph->appendChild($grandChild);
                    }
                } else {
                    $paragraph->appendChild($child);
                }
                static::trimLeadingSpace($paragraph);
                // Nota que e so um endereco web: justificada, a folga da linha
                // inteira caia no espaco depois do numero.
                $text = trim(preg_replace('/^\s*' . $number . '\s*/u', '', $paragraph->textContent));
                if (preg_match('#^(https?://|www\.)\S+$#i', $text)) {
                    $paragraph->setAttribute('style', 'font-size: 9pt; text-align: left; overflow-wrap: anywhere');
                }
                $container->appendChild($paragraph);
            }
        }
        $body->appendChild($container);
    }

    /**
     * Listas do Word viram paragrafos com o rotulo escrito ("a)", "d.1)", "(i)", "•").
     *
     * O LibreOffice exporta um item do 3o nivel de uma lista de varios niveis
     * como lista > item vazio > lista > item vazio > lista > item; cada nivel
     * vazio desenhava o proprio numero ("1. 3. a." onde o Word mostra "a)").
     * O HTML tambem nao guarda o formato do numero do Word ("a)", "d.1)").
     * Com os rotulos vindos da conversao para texto, cada item sai como no
     * Word; sem eles, o rotulo e montado pelo tipo/inicio da lista.
     */
    protected static function convertLists(DOMDocument $dom, DOMXPath $xpath, array $listLabels)
    {
        $state = ['labels' => array_values($listLabels), 'pointer' => 0];
        $roots = $xpath->query('//ol[not(ancestor::ol) and not(ancestor::ul)] | //ul[not(ancestor::ol) and not(ancestor::ul)]');
        foreach (iterator_to_array($roots) as $list) {
            $paragraphs = [];
            static::collectListItems($dom, $list, 0, $paragraphs, $state);
            foreach ($paragraphs as $paragraph) {
                $list->parentNode->insertBefore($paragraph, $list);
            }
            $list->parentNode->removeChild($list);
        }
    }

    protected static function collectListItems(DOMDocument $dom, DOMElement $list, $depth, array &$paragraphs, array &$state)
    {
        $ordered = strtolower($list->nodeName) === 'ol';
        $number = $list->hasAttribute('start') ? max(1, (int) $list->getAttribute('start')) : 1;
        $type = $list->getAttribute('type') ?: '1';

        foreach (iterator_to_array($list->childNodes) as $item) {
            if (!$item instanceof DOMElement) {
                continue;
            }
            $tag = strtolower($item->nodeName);
            if ($tag === 'ol' || $tag === 'ul') {
                // Lista direto dentro de lista: nivel sem item proprio.
                static::collectListItems($dom, $item, $depth, $paragraphs, $state);
                continue;
            }
            if ($tag !== 'li') {
                continue;
            }

            $fallback = $ordered ? static::listMarker($number, $type) . '.' : '•';
            $number++;
            $own = [];
            $nested = [];
            foreach (iterator_to_array($item->childNodes) as $child) {
                $childTag = $child instanceof DOMElement ? strtolower($child->nodeName) : '';
                if ($childTag === 'ol' || $childTag === 'ul') {
                    $nested[] = $child;
                } else {
                    $own[] = $child;
                }
            }

            $text = '';
            $hasMedia = false;
            foreach ($own as $node) {
                $text .= ' ' . $node->textContent;
                if ($node instanceof DOMElement && ($node->getElementsByTagName('img')->length || $node->getElementsByTagName('table')->length)) {
                    $hasMedia = true;
                }
            }
            $hasContent = static::normalizeListText($text) !== '' || $hasMedia;

            if ($hasContent) {
                $found = static::takeListLabel($state, $text);
                $label = $found ? $found['label'] : $fallback;
                // Nivel do Word (1 = primeiro). Os dois primeiros ficam na margem,
                // como os itens "1." e os titulos "3. DOS PEDIDOS" no Word.
                $itemDepth = $found && !empty($found['level']) ? max(0, (int) $found['level'] - 2) : $depth;
                static::appendListParagraphs($dom, $own, $label, $itemDepth, $paragraphs);
            }
            // Item vazio (so abre o proximo nivel) nao recua: o Word nao mostra esse nivel.
            foreach ($nested as $nestedList) {
                static::collectListItems($dom, $nestedList, $hasContent ? $depth + 1 : $depth, $paragraphs, $state);
            }
        }
    }

    /** Paragrafos do item: o primeiro leva o rotulo pendurado a esquerda. */
    protected static function appendListParagraphs(DOMDocument $dom, array $nodes, $label, $depth, array &$paragraphs)
    {
        $left = round(self::LIST_INDENT_CM * $depth + self::LIST_LABEL_CM, 2);
        $state = ['first' => true, 'current' => null];
        $start = function ($source) use ($dom, $left, $label, &$state, &$paragraphs) {
            $paragraph = $dom->createElement('p');
            $style = $source instanceof DOMElement ? $source->getAttribute('style') : '';
            $style = trim(preg_replace('/\s*(margin-left|text-indent)\s*:[^;]*;?/i', '', $style), '; ');
            $style .= ($style !== '' ? '; ' : '') . 'margin-left: ' . $left . 'cm';
            if ($state['first']) {
                $style .= '; text-indent: -' . self::LIST_LABEL_CM . 'cm';
                $marker = $dom->createElement('span');
                $marker->appendChild($dom->createTextNode($label));
                $marker->setAttribute('style', 'display: inline-block; min-width: ' . self::LIST_LABEL_CM . 'cm; text-indent: 0');
                $paragraph->appendChild($marker);
                $state['first'] = false;
            }
            $paragraph->setAttribute('style', $style);
            $paragraphs[] = $paragraph;

            return $paragraph;
        };

        foreach ($nodes as $node) {
            $isBlock = $node instanceof DOMElement && in_array(strtolower($node->nodeName), ['p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true);
            if ($isBlock) {
                $paragraph = $start($node);
                while ($node->firstChild) {
                    $paragraph->appendChild($node->firstChild);
                }
                $state['current'] = null;
                continue;
            }
            if ($node->nodeType === XML_TEXT_NODE && trim($node->nodeValue) === '' && $state['current'] === null) {
                continue;
            }
            if ($state['current'] === null) {
                $state['current'] = $start(null);
            }
            $state['current']->appendChild($node);
        }
        if ($state['first']) {
            $start(null);
        }
    }

    /** Proximo rotulo do Word cujo texto comeca como o item (procura adiante, sem voltar). */
    protected static function takeListLabel(array &$state, $text)
    {
        $needle = mb_substr(static::normalizeListText($text), 0, 30, 'UTF-8');
        if ($needle === '') {
            return null;
        }
        $total = count($state['labels']);
        for ($index = $state['pointer']; $index < $total && $index < $state['pointer'] + 60; $index++) {
            $candidate = static::normalizeListText($state['labels'][$index]['text'] ?? '');
            $prefix = mb_substr($candidate, 0, 30, 'UTF-8');
            if ($candidate !== '' && (strpos($candidate, $needle) === 0 || strpos($needle, $prefix) === 0)) {
                $state['pointer'] = $index + 1;

                return [
                    'label' => (string) $state['labels'][$index]['label'],
                    'level' => (int) ($state['labels'][$index]['level'] ?? 0),
                ];
            }
        }

        return null;
    }

    protected static function normalizeListText($text)
    {
        $text = str_replace(["\xC2\xA0", "\xE2\x80\x8B"], ' ', (string) $text);

        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $text)), 'UTF-8');
    }

    protected static function listMarker($number, $type)
    {
        if ($type === 'a' || $type === 'A') {
            $letters = '';
            for ($n = $number; $n > 0; $n = intdiv($n - 1, 26)) {
                $letters = chr(97 + (($n - 1) % 26)) . $letters;
            }

            return $type === 'A' ? strtoupper($letters) : $letters;
        }
        if ($type === 'i' || $type === 'I') {
            $map = ['m' => 1000, 'cm' => 900, 'd' => 500, 'cd' => 400, 'c' => 100, 'xc' => 90, 'l' => 50, 'xl' => 40, 'x' => 10, 'ix' => 9, 'v' => 5, 'iv' => 4, 'i' => 1];
            $roman = '';
            foreach ($map as $symbol => $value) {
                while ($number >= $value) {
                    $roman .= $symbol;
                    $number -= $value;
                }
            }

            return $type === 'I' ? strtoupper($roman) : $roman;
        }

        return (string) $number;
    }

    /** Deixa um espaco so entre o numero da nota e o texto dela. */
    protected static function trimLeadingSpace(DOMElement $paragraph)
    {
        $skipSup = $paragraph->firstChild && strtolower($paragraph->firstChild->nodeName) === 'sup';
        $xpath = new DOMXPath($paragraph->ownerDocument);
        foreach (iterator_to_array($xpath->query('.//text()', $paragraph)) as $text) {
            if ($skipSup && $text->parentNode === $paragraph->firstChild) {
                continue;
            }
            $value = ltrim(str_replace("\xC2\xA0", ' ', $text->nodeValue));
            if ($value === '') {
                $text->nodeValue = '';
                continue;
            }
            $text->nodeValue = $value;
            break;
        }
        if ($skipSup) {
            $paragraph->insertBefore($paragraph->ownerDocument->createTextNode(' '), $paragraph->firstChild->nextSibling);
        }
    }

    protected static function convertElements(DOMDocument $dom, DOMXPath $xpath)
    {
        // Titulos do Word (estilo "Titulo 1") viram paragrafo: o tamanho e o
        // negrito ja vem nos estilos do proprio trecho.
        foreach (iterator_to_array($xpath->query('//h1|//h2|//h3|//h4|//h5|//h6')) as $heading) {
            static::rename($dom, $heading, 'p');
        }

        foreach (iterator_to_array($xpath->query('//font')) as $font) {
            $style = [];
            if ($font->hasAttribute('face')) {
                $style[] = 'font-family: ' . $font->getAttribute('face');
            }
            if ($font->hasAttribute('color')) {
                $style[] = 'color: ' . $font->getAttribute('color');
            }
            if ($font->hasAttribute('style')) {
                $style[] = $font->getAttribute('style');
            }
            $span = static::rename($dom, $font, 'span', ['face', 'color', 'size', 'style']);
            if ($style) {
                $span->setAttribute('style', implode('; ', $style));
            }
        }

        // align vence o "text-align: left" generico que o CSS do LibreOffice
        // poe em todo paragrafo (por isso vai no fim do estilo).
        foreach (iterator_to_array($xpath->query('//*[@align]')) as $element) {
            $align = strtolower(trim($element->getAttribute('align')));
            $element->removeAttribute('align');
            if (in_array($align, ['left', 'right', 'center', 'justify'], true) && strtolower($element->nodeName) !== 'table') {
                $element->setAttribute('style', $element->getAttribute('style') . '; text-align: ' . $align);
            }
        }

        // <center><table> -> tabela centralizada.
        foreach (iterator_to_array($xpath->query('//center')) as $center) {
            foreach ($xpath->query('./table', $center) as $table) {
                $table->setAttribute('style', $table->getAttribute('style') . '; margin-left: auto; margin-right: auto');
            }
            $div = static::rename($dom, $center, 'div');
            $div->setAttribute('style', 'text-align: center');
        }

        // O LibreOffice poe cada item numerado numa lista propria; a margem
        // padrao das listas no navegador (1 linha antes e depois) nao existe no
        // Word e espichava a peca.
        foreach (iterator_to_array($xpath->query('//ol | //ul')) as $list) {
            $style = $list->getAttribute('style');
            if (stripos($style, 'margin') === false) {
                $list->setAttribute('style', trim($style . '; margin-top: 0; margin-bottom: 0', '; '));
            }
        }

        static::convertTextFrames($dom, $xpath);

        foreach (iterator_to_array($xpath->query('//a[not(@href) and not(node())]')) as $anchor) {
            $anchor->parentNode->removeChild($anchor);
        }
        foreach (iterator_to_array($xpath->query('//*[@hspace or @vspace or @clear]')) as $element) {
            $element->removeAttribute('hspace');
            $element->removeAttribute('vspace');
            $element->removeAttribute('clear');
        }

        static::collapseWhitespace($xpath);
    }

    /**
     * Caixas de texto do Word: o LibreOffice as posiciona por coordenadas
     * (position: absolute), o que sobrepoe o texto no editor e no PDF. A
     * moldura desenhada (imagem "Forme1") sai; a caixa vira um bloco com borda,
     * a direita, antes do paragrafo onde estava ancorada.
     */
    protected static function convertTextFrames(DOMDocument $dom, DOMXPath $xpath)
    {
        foreach (iterator_to_array($xpath->query('//span[contains(@style, "absolute")][img and not(.//p)]')) as $shape) {
            if (trim($shape->textContent) === '') {
                $shape->parentNode->removeChild($shape);
            }
        }

        foreach (iterator_to_array($xpath->query('//span[contains(@style, "absolute")][.//p]')) as $frame) {
            $width = preg_match('/(?:^|;)\s*width\s*:\s*([^;]+)/i', $frame->getAttribute('style'), $m) ? trim($m[1]) : '6cm';
            $box = static::rename($dom, $frame, 'div', ['id', 'style']);
            $box->setAttribute('style', 'border: 1px solid #000000; padding: 2pt 6pt; margin: 0 0 6pt auto; width: ' . $width);
            $block = $box->parentNode;
            while ($block && !in_array(strtolower($block->nodeName), ['p', 'div', 'td', 'body', 'li'], true)) {
                $block = $block->parentNode;
            }
            if ($block && strtolower($block->nodeName) === 'p' && $block->parentNode) {
                $block->parentNode->insertBefore($box, $block);
            }
        }
    }

    /**
     * Junta espacos/quebras de linha do codigo HTML num espaco so (o navegador
     * ja mostra assim): a frase fica igual a do Word para a troca pelo campo.
     */
    protected static function collapseWhitespace(DOMXPath $xpath)
    {
        foreach (iterator_to_array($xpath->query('//text()[not(ancestor::pre)]')) as $text) {
            $value = preg_replace('/[ \t\r\n]+/', ' ', $text->nodeValue);
            // Codigo HTML escrito como texto no Word (colado da internet), ex.:
            // &quot;RECURSO ESPECIAL&quot;: vira o caractere.
            if (strpos($value, '&') !== false) {
                $value = preg_replace_callback('/&(quot|amp|apos|lt|gt|nbsp|#\d{2,5});/', function ($m) {
                    return html_entity_decode($m[0], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }, $value);
            }
            if ($value !== $text->nodeValue) {
                $text->nodeValue = $value;
            }
        }
    }

    protected static function rename(DOMDocument $dom, DOMElement $element, $name, array $skipAttributes = [])
    {
        $new = $dom->createElement($name);
        foreach (iterator_to_array($element->attributes) as $attribute) {
            if (!in_array(strtolower($attribute->nodeName), $skipAttributes, true)) {
                $new->setAttribute($attribute->nodeName, $attribute->value);
            }
        }
        while ($element->firstChild) {
            $new->appendChild($element->firstChild);
        }
        $element->parentNode->replaceChild($new, $element);

        return $new;
    }

    protected static function cleanAttributes(DOMXPath $xpath)
    {
        foreach (iterator_to_array($xpath->query('//*')) as $element) {
            foreach (['lang', 'dir', 'name', 'title'] as $attribute) {
                $element->removeAttribute($attribute);
            }
            $class = $element->getAttribute('class');
            if ($class !== '' && $class !== 'peticao-notas') {
                $element->removeAttribute('class');
            }
            if ($element->hasAttribute('style')) {
                $style = static::cleanStyle($element->getAttribute('style'));
                if ($style === '') {
                    $element->removeAttribute('style');
                } else {
                    $element->setAttribute('style', $style);
                }
            }
        }
    }

    public static function cleanStyle($style)
    {
        $kept = [];
        foreach (explode(';', (string) $style) as $declaration) {
            if (strpos($declaration, ':') === false) {
                continue;
            }
            list($property, $value) = array_map('trim', explode(':', $declaration, 2));
            $property = strtolower($property);
            $lowerValue = strtolower($value);
            if ($property === '' || $value === '') {
                continue;
            }
            foreach (self::DROP_PROPERTIES as $drop) {
                if ($property === $drop || (substr($drop, -1) === '-' && strpos($property, $drop) === 0)) {
                    continue 2;
                }
            }
            if ($property === 'font-family') {
                $fonts = array_map(function ($font) {
                    return strtolower(trim($font, " \t\"'"));
                }, explode(',', $value));
                // Fica so fonte de verdade diferente da padrao; nomes internos do
                // Word/PDF (ex.: "TimesNewRomanNormal") caiam numa fonte qualquer.
                if (!array_diff($fonts, self::DEFAULT_FONTS) || !in_array($fonts[0], self::KNOWN_FONTS, true)) {
                    continue;
                }
            }
            if ($property === 'background' && $lowerValue === 'transparent') {
                continue;
            }
            if ($property === 'color' && in_array($lowerValue, ['#000000', '#000', 'black'], true)) {
                continue;
            }
            // A ultima declaracao vale (estilo interno sobrepoe o herdado).
            $kept[$property] = $property . ': ' . $value;
        }

        return implode('; ', $kept);
    }

    /** <span> sem atributo nenhum nao faz nada: fica so o conteudo. */
    protected static function unwrapBareSpans(DOMXPath $xpath)
    {
        $changed = false;
        foreach (iterator_to_array($xpath->query('//span[not(@*)] | //span[not(node())]')) as $span) {
            static::unwrap($span);
            $changed = true;
        }

        return $changed;
    }

    /** <span a><span b>texto</span></span> -> <span a;b>texto</span>. */
    protected static function flattenNestedSpans(DOMXPath $xpath)
    {
        $changed = false;
        foreach (iterator_to_array($xpath->query('//span[count(*) = 1 and span and normalize-space(text()) = ""]')) as $outer) {
            if ($outer->attributes->length !== ($outer->hasAttribute('style') ? 1 : 0)) {
                continue;
            }
            $inner = null;
            $onlyWhitespace = true;
            foreach ($outer->childNodes as $child) {
                if ($child instanceof DOMElement) {
                    $inner = $child;
                } elseif (trim($child->nodeValue) !== '') {
                    $onlyWhitespace = false;
                }
            }
            if (!$inner || !$onlyWhitespace || $inner->attributes->length !== ($inner->hasAttribute('style') ? 1 : 0)) {
                continue;
            }
            $style = static::cleanStyle($outer->getAttribute('style') . ';' . $inner->getAttribute('style'));
            if ($style === '') {
                $inner->removeAttribute('style');
            } else {
                $inner->setAttribute('style', $style);
            }
            static::unwrap($outer);
            $changed = true;
        }

        return $changed;
    }

    /** Spans (e b, i, u) vizinhos, sem nada entre eles e com o mesmo estilo, viram um so. */
    protected static function mergeSiblingSpans(DOMXPath $xpath)
    {
        $changed = false;
        foreach (iterator_to_array($xpath->query('//span|//b|//strong|//i|//em|//u')) as $span) {
            if (!$span->parentNode) {
                continue;
            }
            $next = $span->nextSibling;
            while ($next instanceof DOMElement
                && strtolower($next->nodeName) === strtolower($span->nodeName)
                && static::sameAttributes($span, $next)) {
                while ($next->firstChild) {
                    $span->appendChild($next->firstChild);
                }
                $remove = $next;
                $next = $next->nextSibling;
                $remove->parentNode->removeChild($remove);
                $changed = true;
            }
        }

        return $changed;
    }

    protected static function sameAttributes(DOMElement $a, DOMElement $b)
    {
        if ($a->attributes->length !== $b->attributes->length) {
            return false;
        }
        foreach ($a->attributes as $attribute) {
            if ($b->getAttribute($attribute->nodeName) !== $attribute->value) {
                return false;
            }
        }

        return true;
    }

    protected static function unwrap(DOMNode $node)
    {
        $parent = $node->parentNode;
        while ($node->firstChild) {
            $parent->insertBefore($node->firstChild, $node);
        }
        $parent->removeChild($node);
    }
}
