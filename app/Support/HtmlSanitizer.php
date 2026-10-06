<?php

namespace App\Support;

use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Limpa o HTML das peticoes antes de guardar ou renderizar.
 *
 * O conteudo vem do editor (CKEditor) e de arquivos Word importados, entao
 * precisa preservar paragrafos, tabelas, estilos inline, fontes e imagens
 * embutidas (data:image). O objetivo e so tirar o que executa codigo quando a
 * peca e reaberta no navegador ou gerada em PDF: <script>, <style>, <iframe>,
 * formularios, manipuladores onX e URLs javascript:. Nao e um substituto de
 * um HTML Purifier completo; e uma faxina conservadora sobre a arvore DOM.
 */
class HtmlSanitizer
{
    /**
     * Elementos removidos com todo o seu conteudo: executam codigo, carregam
     * recursos externos ou coletam dados, e nunca fazem parte de uma peca.
     */
    const FORBIDDEN_ELEMENTS = [
        'script', 'style', 'iframe', 'frame', 'frameset', 'object', 'embed',
        'applet', 'form', 'input', 'button', 'select', 'option', 'optgroup',
        'textarea', 'fieldset', 'label', 'legend', 'link', 'meta', 'base',
        'title', 'svg', 'math', 'audio', 'video', 'source', 'track', 'canvas',
        'noscript', 'template', 'portal', 'object', 'xml', 'head',
    ];

    /**
     * Atributos que podem carregar uma URL; o esquema de cada um e conferido.
     */
    const URL_ATTRIBUTES = [
        'href', 'src', 'action', 'formaction', 'background', 'poster',
        'longdesc', 'cite', 'usemap', 'data', 'xlink:href', 'lowsrc', 'dynsrc',
    ];

    public static function clean($html)
    {
        $html = (string) $html;
        if (trim($html) === '') {
            return $html;
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        // O prefixo forca UTF-8; as flags evitam que <html>/<body> sejam inventados.
        $loaded = $dom->loadHTML(
            '<?xml encoding="UTF-8"><body>' . $html . '</body>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET
        );
        libxml_clear_errors();

        if (!$loaded) {
            // Nao conseguimos entender o HTML: devolve so o texto, sem marcacao.
            return htmlspecialchars(strip_tags($html), ENT_QUOTES, 'UTF-8');
        }

        $body = $dom->getElementsByTagName('body')->item(0);
        if (!$body) {
            return htmlspecialchars(strip_tags($html), ENT_QUOTES, 'UTF-8');
        }

        static::cleanNode($body);

        $out = '';
        foreach (iterator_to_array($body->childNodes) as $child) {
            $out .= $dom->saveHTML($child);
        }

        return trim($out);
    }

    protected static function cleanNode(DOMNode $node)
    {
        // Copia porque vamos remover filhos durante a iteracao.
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $name = strtolower($child->localName ?: $child->nodeName);

                if (in_array($name, static::FORBIDDEN_ELEMENTS, true)) {
                    $child->parentNode->removeChild($child);
                    continue;
                }

                static::cleanAttributes($child);
                static::cleanNode($child);
                continue;
            }

            // Comentarios podem esconder condicionais do IE com script.
            if ($child->nodeType === XML_COMMENT_NODE) {
                $child->parentNode->removeChild($child);
            }
        }
    }

    protected static function cleanAttributes(DOMElement $element)
    {
        foreach (iterator_to_array($element->attributes) as $attribute) {
            if (!$attribute instanceof DOMAttr) {
                continue;
            }

            $name = strtolower($attribute->nodeName);
            $value = (string) $attribute->value;

            // Qualquer manipulador de evento (onclick, onerror, onload...).
            if (strpos($name, 'on') === 0) {
                $element->removeAttribute($attribute->nodeName);
                continue;
            }

            if ($name === 'srcdoc' || $name === 'srcset') {
                $element->removeAttribute($attribute->nodeName);
                continue;
            }

            if ($name === 'style') {
                if (static::styleIsDangerous($value)) {
                    $element->removeAttribute($attribute->nodeName);
                }
                continue;
            }

            if (in_array($name, static::URL_ATTRIBUTES, true) && static::urlIsDangerous($name, $value)) {
                $element->removeAttribute($attribute->nodeName);
            }
        }
    }

    protected static function styleIsDangerous($style)
    {
        $normalized = static::decodeForScan($style);

        return (bool) preg_match('/(expression\s*\(|javascript\s*:|vbscript\s*:|behavior\s*:|@import|url\s*\(\s*["\']?\s*(?:javascript|vbscript|data)\s*:)/i', $normalized);
    }

    protected static function urlIsDangerous($attributeName, $value)
    {
        $normalized = static::decodeForScan($value);

        // data:image/* e util (imagens embutidas do Word/preview); so em src/lowsrc/dynsrc.
        if (preg_match('/^\s*data\s*:/i', $normalized)) {
            $allowsDataImage = in_array($attributeName, ['src', 'lowsrc', 'dynsrc'], true);

            return !($allowsDataImage && preg_match('#^\s*data\s*:\s*image/#i', $normalized));
        }

        return (bool) preg_match('/^\s*(?:javascript|vbscript|livescript|mocha|about)\s*:/i', $normalized);
    }

    /**
     * Neutraliza os disfarces comuns antes de olhar o esquema: espacos/brancos
     * no meio ("java\tscript:"), entidades HTML e caracteres de controle.
     */
    protected static function decodeForScan($value)
    {
        $value = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/[\x00-\x20\x7f]+/u', '', $value);

        return $value === null ? '' : $value;
    }
}
