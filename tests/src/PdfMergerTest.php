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
use Derafu\Merger\Type\PdfMerger;
use Mpdf\Mpdf;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use setasign\Fpdi\PdfParser\StreamReader;

#[CoversClass(PdfMerger::class)]
class PdfMergerTest extends TestCase
{
    // The `MissingOptionalDependencyException` branch of `merge()` cannot be
    // exercised here: `mpdf/mpdf` is a `require-dev` dependency of this
    // package, so it is always installed while running its own test suite.

    public function testMergesMultiplePdfsPageByPage(): void
    {
        $merger = new PdfMerger();

        $pdfA = $this->makeOnePagePdf('Uno');
        $pdfB = $this->makeTwoPagePdf('Dos', 'Tres');

        $result = $merger->merge([$pdfA, $pdfB]);

        $this->assertSame(3, $this->countPages($result));
    }

    public function testMergingASinglePdfKeepsItsPageCount(): void
    {
        $merger = new PdfMerger();

        $pdf = $this->makeTwoPagePdf('Uno', 'Dos');

        $result = $merger->merge([$pdf]);

        $this->assertSame(2, $this->countPages($result));
    }

    public function testAcceptsMpdfConstructorOptions(): void
    {
        $merger = new PdfMerger();

        $pdf = $this->makeOnePagePdf('Uno');

        $result = $merger->merge(
            [$pdf],
            ['mpdf' => ['format' => 'A5']]
        );

        $this->assertSame(1, $this->countPages($result));
    }

    public function testThrowsWhenMergingAnEmptyList(): void
    {
        $merger = new PdfMerger();

        $this->expectException(MergerException::class);

        $merger->merge([]);
    }

    public function testSupportsOnlyPdf(): void
    {
        $merger = new PdfMerger();

        $this->assertTrue($merger->supports('application/pdf'));
        $this->assertFalse($merger->supports('text/html'));
        $this->assertFalse($merger->supports('application/octet-stream'));
    }

    private function makeOnePagePdf(string $text): string
    {
        $mpdf = new Mpdf();
        $mpdf->WriteHTML('<p>' . $text . '</p>');

        return $mpdf->Output('', 'S');
    }

    private function makeTwoPagePdf(string $textA, string $textB): string
    {
        $mpdf = new Mpdf();
        $mpdf->WriteHTML('<p>' . $textA . '</p>');
        $mpdf->AddPage();
        $mpdf->WriteHTML('<p>' . $textB . '</p>');

        return $mpdf->Output('', 'S');
    }

    private function countPages(string $pdf): int
    {
        $mpdf = new Mpdf();

        return $mpdf->setSourceFile(StreamReader::createByString($pdf));
    }
}
