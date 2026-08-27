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
use Derafu\Merger\Exception\MissingOptionalDependencyException;
use Mpdf\Mpdf;
use setasign\Fpdi\PdfParser\StreamReader;

/**
 * Merges PDF documents into a single one, page by page, in order.
 *
 * Needs `mpdf/mpdf` (and its own `setasign/fpdi` dependency), which this
 * package only requires optionally (`require-dev`/`suggest`) — most
 * consumers of `derafu/merger` will never merge PDFs specifically.
 */
final class PdfMerger implements MergerInterface
{
    /**
     * {@inheritDoc}
     *
     * @param array{mpdf?: array<string,mixed>} $options `mpdf`: constructor
     * configuration passed to `Mpdf` (see mPDF's own documentation).
     * @throws MissingOptionalDependencyException If `mpdf/mpdf` is not
     * installed.
     */
    public function merge(array $contents, array $options = []): string
    {
        if (empty($contents)) {
            throw new MergerException(
                'Cannot merge an empty list of contents.'
            );
        }

        if (!class_exists(Mpdf::class)) {
            throw MissingOptionalDependencyException::forFeature(
                self::class . '::merge()',
                'mpdf/mpdf'
            );
        }

        $mpdf = new Mpdf($options['mpdf'] ?? []);

        foreach ($contents as $content) {
            $pageCount = $mpdf->setSourceFile(
                StreamReader::createByString($content)
            );

            for ($page = 1; $page <= $pageCount; $page++) {
                $templateId = $mpdf->importPage($page);
                $size = $mpdf->getTemplateSize($templateId);
                $mpdf->AddPageByArray([
                    'orientation' => $size['width'] > $size['height']
                        ? 'L'
                        : 'P',
                ]);
                $mpdf->useTemplate($templateId);
            }
        }

        return $mpdf->Output('', 'S');
    }

    /**
     * {@inheritDoc}
     */
    public function supports(string $mimeType): bool
    {
        return $mimeType === 'application/pdf';
    }
}
