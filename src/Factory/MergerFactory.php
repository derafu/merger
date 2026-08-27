<?php

declare(strict_types=1);

/**
 * Derafu: Merger - Combine Multiple Files Into One, By Format.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Merger\Factory;

use Derafu\Merger\Merger;
use Derafu\Merger\Type\HtmlMerger;
use Derafu\Merger\Type\PdfMerger;
use Derafu\Merger\Type\RawMerger;
use Mpdf\Mpdf;

/**
 * Builds a `Merger` with the mergers this package ships, already registered
 * in the right order, so a consumer that just wants the defaults does not
 * have to assemble the list by hand.
 */
final class MergerFactory
{
    /**
     * Creates a `Merger` with `PdfMerger` (only if `mpdf/mpdf` is
     * installed), `HtmlMerger`, and `RawMerger` (as the catch-all, always
     * last).
     *
     * @return Merger
     */
    public static function create(): Merger
    {
        $merger = new Merger();

        if (class_exists(Mpdf::class)) {
            $merger->addMerger(new PdfMerger());
        }

        $merger->addMerger(new HtmlMerger());
        $merger->addMerger(new RawMerger());

        return $merger;
    }
}
