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

use Derafu\Merger\Contract\MergerInterface;
use Derafu\Merger\Exception\MergerException;
use Derafu\Merger\Type\HtmlMerger;
use Derafu\Merger\Type\PdfMerger;
use Derafu\Merger\Type\RawMerger;
use DOMDocument;
use DOMXPath;
use Mpdf\Mpdf;
use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\TestCase;
use setasign\Fpdi\PdfParser\StreamReader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

/**
 * Exercise real format operations and consumer contributions through the YAML.
 */
#[CoversNamespace('Derafu\Merger')]
final class ServicesTest extends TestCase
{
    private function container(string $config = 'services.yaml'): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../fixtures/config'));
        $loader->load($config);
        $container->compile();

        return $container;
    }

    private function merger(ContainerBuilder $container): MergerInterface
    {
        $merger = $container->get('test.merger');
        $this->assertInstanceOf(MergerInterface::class, $merger);

        return $merger;
    }

    public function testBaseConfigurationCombinesHtmlBeforeTheRawFallback(): void
    {
        $merger = $this->merger($this->container());
        $this->assertInstanceOf(HtmlMerger::class, $merger->getMerger('text/html'));
        $result = $merger->merge([
            '<html><head><style>.first{color:red}</style></head><body><p>First</p></body></html>',
            '<html><head><style>.second{color:blue}</style></head><body><p>Second</p></body></html>',
        ], 'text/html', ['pageBreak' => '<hr>']);

        $document = new DOMDocument();
        $this->assertTrue($document->loadHTML($result));
        $xpath = new DOMXPath($document);
        $this->assertSame(1, $document->getElementsByTagName('html')->length);
        $this->assertSame(2, $document->getElementsByTagName('style')->length);
        $this->assertSame('First', $xpath->evaluate('string(/html/body/p[1])'));
        $this->assertSame('Second', $xpath->evaluate('string(/html/body/p[2])'));
        $this->assertSame(1, $document->getElementsByTagName('hr')->length);
    }

    public function testBaseConfigurationConcatenatesUnknownFormatsWithTheirSeparator(): void
    {
        $merger = $this->merger($this->container());
        $this->assertInstanceOf(RawMerger::class, $merger->getMerger('application/x-printer'));
        $this->assertSame("first\0|second\x1b", $merger->merge(
            ["first\0", "second\x1b"],
            'application/x-printer',
            ['separator' => '|']
        ));
    }

    public function testPdfSupportRequiresItsImportEvenWhenMpdfIsInstalled(): void
    {
        $merger = $this->merger($this->container());
        $this->assertInstanceOf(RawMerger::class, $merger->getMerger('application/pdf'));
    }

    public function testOptionalConfigurationCombinesRealPdfPagesInOrder(): void
    {
        $merger = $this->merger($this->container('pdf-services.yaml'));
        $this->assertInstanceOf(PdfMerger::class, $merger->getMerger('application/pdf'));
        $landscape = new Mpdf(['orientation' => 'L']);
        $landscape->WriteHTML('<p>First</p>');
        $portrait = new Mpdf(['orientation' => 'P']);
        $portrait->WriteHTML('<p>Second</p>');
        $result = $merger->merge([
            $landscape->Output('', 'S'),
            $portrait->Output('', 'S'),
        ], 'application/pdf');

        $reader = new Mpdf();
        $this->assertSame(2, $reader->setSourceFile(StreamReader::createByString($result)));
        $first = $reader->getTemplateSize($reader->importPage(1));
        $second = $reader->getTemplateSize($reader->importPage(2));
        $this->assertIsArray($first);
        $this->assertIsArray($second);
        $this->assertGreaterThan($first['height'], $first['width']);
        $this->assertGreaterThan($second['width'], $second['height']);
        $this->assertInstanceOf(HtmlMerger::class, $merger->getMerger('text/html'));
        $this->assertSame('ab', $merger->merge(['a', 'b'], 'unknown/type'));
    }

    public function testConsumerContributionOverridesHtmlAndPreservesTheOtherFormats(): void
    {
        $merger = $this->merger($this->container('custom-services.yaml'));
        $this->assertSame(
            '<article><p>First</p></article><article><p>Second</p></article>',
            $merger->merge(['<p>First</p>', '<p>Second</p>'], 'text/html')
        );
        $this->assertInstanceOf(PdfMerger::class, $merger->getMerger('application/pdf'));
        $this->assertSame('a|b', $merger->merge(['a', 'b'], 'unknown/type', ['separator' => '|']));
    }

    public function testTaggedResourcesTranslateARealInvalidSeparatorException(): void
    {
        $container = $this->container();
        $merger = $this->merger($container);
        $translator = $container->get('test.translator');
        $this->assertInstanceOf(Translator::class, $translator);
        try {
            $merger->merge(['first', 'second'], 'text/plain', ['separator' => 42]);
            $this->fail('An invalid merge separator was accepted.');
        } catch (MergerException $exception) {
            $this->assertSame('El separador de unión debe ser un string; se recibió int.', $exception->trans($translator));
        }
    }
}
