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

use Composer\Autoload\ClassLoader;
use Derafu\Merger\Exception\MergerException;
use Derafu\Merger\Exception\MissingOptionalDependencyException;
use Derafu\Merger\Translation\MergerTranslationResourceProvider;
use Derafu\Merger\Type\PdfMerger;
use Derafu\Translation\TranslatorFactory;
use Mpdf\Mpdf;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use setasign\Fpdi\PdfParser\StreamReader;

#[CoversClass(PdfMerger::class)]
#[UsesClass(MissingOptionalDependencyException::class)]
#[UsesClass(MergerTranslationResourceProvider::class)]
class PdfMergerTest extends TestCase
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testReportsAMissingOptionalDependencyBeforeReadingPdfContents(): void
    {
        $this->assertFalse(class_exists(Mpdf::class, false));
        $loader = require dirname(__DIR__, 2) . '/vendor/autoload.php';
        $this->assertInstanceOf(ClassLoader::class, $loader);
        $directories = $loader->getPrefixesPsr4()['Mpdf\\'];
        $loader->setPsr4('Mpdf\\', []);

        try {
            try {
                (new PdfMerger())->merge(['Unread PDF content']);
                $this->fail('PDF merging must report the missing dependency.');
            } catch (MissingOptionalDependencyException $exception) {
                $feature = PdfMerger::class . '::merge()';
                $this->assertSame("$feature requires mpdf/mpdf, which is not installed. Run: composer require mpdf/mpdf", $exception->getMessage());
                $translator = TranslatorFactory::create('es', resourceProviders: [new MergerTranslationResourceProvider()]);
                $this->assertSame("$feature requiere mpdf/mpdf, que no está instalado. Ejecuta: composer require mpdf/mpdf", $exception->trans($translator));
            }
        } finally {
            $loader->setPsr4('Mpdf\\', $directories);
        }
    }

    public function testKeepsLandscapeAndPortraitPagesInTheirOriginalOrder(): void
    {
        $landscape = new Mpdf(['orientation' => 'L']);
        $landscape->WriteHTML('<p>Landscape page</p>');
        $portrait = new Mpdf(['orientation' => 'P']);
        $portrait->WriteHTML('<p>Portrait page</p>');

        $result = (new PdfMerger())->merge([
            $landscape->Output('', 'S'),
            $portrait->Output('', 'S'),
        ]);

        $reader = new Mpdf();
        $this->assertSame(2, $reader->setSourceFile(StreamReader::createByString($result)));
        $first = $reader->getTemplateSize($reader->importPage(1));
        $second = $reader->getTemplateSize($reader->importPage(2));
        $this->assertIsArray($first);
        $this->assertIsArray($second);
        $this->assertGreaterThan($first['height'], $first['width']);
        $this->assertGreaterThan($second['width'], $second['height']);
    }

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
