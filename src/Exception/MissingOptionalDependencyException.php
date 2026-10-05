<?php

declare(strict_types=1);

/**
 * Derafu: Merger - Combine Multiple Files Into One, By Format.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Merger\Exception;

/**
 * Thrown when a merger needs an optional (`require-dev`/`suggest`) package
 * this project does not require directly — e.g. `PdfMerger` needs
 * `mpdf/mpdf`, which most consumers of this package never install because
 * most of them never merge PDFs.
 */
final class MissingOptionalDependencyException extends MergerException
{
    /**
     * @param string $feature The feature that needs it, as `Class::method()`.
     * @param string $package The Composer package to install.
     * @return self
     */
    public static function forFeature(string $feature, string $package): self
    {
        return new self([
            '{feature} requires {package}, which is not installed. Run: composer require {package}',
            'feature' => $feature,
            'package' => $package,
        ]);
    }
}
