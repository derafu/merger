<?php

declare(strict_types=1);

/**
 * Derafu: Merger - Combine Multiple Files Into One, By Format.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Merger\Type;

use Derafu\Merger\Contract\MergerInterface;
use Derafu\Merger\Exception\MergerException;
use DOMDocument;
use DOMNode;

/**
 * Merges HTML documents into a single one.
 *
 * Each content is expected to be a full HTML document (`<html>`, `<head>`,
 * `<body>`). Concatenating full documents as-is would produce invalid HTML
 * (multiple `<html>`/`<head>`/`<body>` tags), so this merger extracts the
 * inner content of `<head>` (styles, etc., concatenated as-is — harmless if
 * repeated) and `<body>` (the visible content) of each document, and
 * assembles a single, valid HTML document with all of them, separating each
 * body with a page break so it prints as one page per original document.
 */
final class HtmlMerger implements MergerInterface
{
    private const DEFAULT_PAGE_BREAK = '<div style="page-break-after: always;"></div>';

    /**
     * {@inheritDoc}
     *
     * @param array{pageBreak?: string} $options `pageBreak`: HTML inserted
     * between each document's body (default: a `page-break-after` `<div>`).
     */
    public function merge(array $contents, array $options = []): string
    {
        if (empty($contents)) {
            throw new MergerException(
                'Cannot merge an empty list of contents.'
            );
        }

        $pageBreak = $options['pageBreak'] ?? self::DEFAULT_PAGE_BREAK;

        $heads = [];
        $bodies = [];
        foreach ($contents as $content) {
            $heads[] = $this->extractTag($content, 'head');
            $bodies[] = $this->extractTag($content, 'body');
        }

        return '<!DOCTYPE html><html><head>'
            . implode('', $heads)
            . '</head><body>'
            . implode($pageBreak, $bodies)
            . '</body></html>'
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function supports(string $mimeType): bool
    {
        return $mimeType === 'text/html';
    }

    /**
     * Extracts the inner HTML of a tag (`head` or `body`) from an HTML
     * document.
     *
     * Parses with `DOMDocument`, tolerating malformed HTML the same way a
     * browser would (errors are suppressed, not thrown) — the individual
     * documents are trusted to already be reasonable HTML, this is not a
     * validator. Encoding is forced to UTF-8 via a leading XML processing
     * instruction, since `DOMDocument::loadHTML()` otherwise assumes
     * ISO-8859-1 and mangles non-ASCII characters.
     *
     * @param string $html
     * @param string $tag `head` or `body`.
     * @return string The tag's inner HTML, or `''` if the tag is missing.
     */
    private function extractTag(string $html, string $tag): string
    {
        $dom = new DOMDocument();

        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $node = $dom->getElementsByTagName($tag)->item(0);
        if ($node === null) {
            return '';
        }

        return implode('', array_map(
            fn (DOMNode $child): string => $dom->saveHTML($child) ?: '',
            iterator_to_array($node->childNodes)
        ));
    }
}
