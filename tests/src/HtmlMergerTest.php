<?php

declare(strict_types=1);

/**
 * Derafu: Merger - Combine Multiple Files Into One, By Format.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsMerger;

use Derafu\Merger\Exception\MergerException;
use Derafu\Merger\Type\HtmlMerger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlMerger::class)]
class HtmlMergerTest extends TestCase
{
    public function testMergesTheBodyOfEachDocumentSeparatedByAPageBreak(): void
    {
        $merger = new HtmlMerger();

        $result = $merger->merge([
            '<html><head></head><body><h1>Uno</h1></body></html>',
            '<html><head></head><body><h1>Dos</h1></body></html>',
        ]);

        $this->assertSame(
            '<!DOCTYPE html><html><head></head><body>'
                . '<h1>Uno</h1>'
                . '<div style="page-break-after: always;"></div>'
                . '<h1>Dos</h1>'
                . '</body></html>',
            $result
        );
    }

    public function testConcatenatesTheHeadOfEachDocument(): void
    {
        $merger = new HtmlMerger();

        $result = $merger->merge([
            '<html><head><style>.a{color:red}</style></head><body>Uno</body></html>',
            '<html><head><style>.b{color:blue}</style></head><body>Dos</body></html>',
        ]);

        $this->assertStringContainsString(
            '<style>.a{color:red}</style><style>.b{color:blue}</style>',
            $result
        );
    }

    public function testPreservesUtf8Characters(): void
    {
        $merger = new HtmlMerger();

        $result = $merger->merge([
            '<html><head></head><body><p>Ñandú áéíóú</p></body></html>',
        ]);

        $this->assertStringContainsString('Ñandú áéíóú', $result);
    }

    public function testAcceptsACustomPageBreak(): void
    {
        $merger = new HtmlMerger();

        $result = $merger->merge(
            [
                '<html><head></head><body>Uno</body></html>',
                '<html><head></head><body>Dos</body></html>',
            ],
            ['pageBreak' => '<hr/>']
        );

        $this->assertStringContainsString('Uno<hr/>Dos', $result);
    }

    public function testAContentFragmentWithoutFullDocumentStructureStillMerges(): void
    {
        // `DOMDocument::loadHTML()` auto-wraps a bare fragment in an
        // implicit `<body>` (but not a `<head>`) — so this exercises both
        // the auto-wrapped body and the "tag missing" branch for `<head>`.
        $merger = new HtmlMerger();

        $result = $merger->merge(['<p>Sin estructura de documento.</p>']);

        $this->assertSame(
            '<!DOCTYPE html><html><head></head><body>'
                . '<p>Sin estructura de documento.</p>'
                . '</body></html>',
            $result
        );
    }

    public function testThrowsWhenMergingAnEmptyList(): void
    {
        $merger = new HtmlMerger();

        $this->expectException(MergerException::class);

        $merger->merge([]);
    }

    public function testSupportsOnlyHtml(): void
    {
        $merger = new HtmlMerger();

        $this->assertTrue($merger->supports('text/html'));
        $this->assertFalse($merger->supports('application/pdf'));
        $this->assertFalse($merger->supports('application/octet-stream'));
    }
}
