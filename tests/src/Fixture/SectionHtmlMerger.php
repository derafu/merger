<?php

declare(strict_types=1);

/**
 * Derafu: Merger - Combine Multiple Files Into One, By Format.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsMerger\Fixture;

use Derafu\Merger\Contract\FormatMergerInterface;

/**
 * Consumer contribution that groups HTML fragments into document sections.
 */
final class SectionHtmlMerger implements FormatMergerInterface
{
    /**
     * {@inheritDoc}
     */
    public function merge(array $contents, array $options = []): string
    {
        return '<article>' . implode('</article><article>', $contents) . '</article>';
    }

    /**
     * {@inheritDoc}
     */
    public function supports(string $mimeType): bool
    {
        return $mimeType === 'text/html';
    }
}
