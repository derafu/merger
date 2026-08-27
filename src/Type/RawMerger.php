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

/**
 * Merges contents by plain concatenation.
 *
 * Correct for any format that is just a sequence of independent, unstructured
 * bytes read one after another — e.g. ESC/POS printer commands, where sending
 * stream A followed by stream B has the exact same effect as printing them as
 * two separate jobs. Also used as the catch-all: `supports()` always returns
 * `true`, so a `Merger` with this registered last never fails to find a
 * merger for a MIME type it does not recognize.
 */
final class RawMerger implements MergerInterface
{
    /**
     * {@inheritDoc}
     */
    public function merge(array $contents, array $options = []): string
    {
        if (empty($contents)) {
            throw new MergerException(
                'Cannot merge an empty list of contents.'
            );
        }

        return implode($options['separator'] ?? '', $contents);
    }

    /**
     * {@inheritDoc}
     */
    public function supports(string $mimeType): bool
    {
        return true;
    }
}
