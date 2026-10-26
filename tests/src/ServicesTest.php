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
use Derafu\Merger\Translation\MergerTranslationResourceProvider;
use Derafu\Merger\Type\RawMerger;
use Derafu\Translation\TranslatorFactory;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

/**
 * Loads the real provider configuration and consumes its tagged resources.
 */
#[CoversNothing]
final class ServicesTest extends TestCase
{
    private function container(): ContainerBuilder
    {
        $root = dirname(__DIR__, 2);
        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator($root . '/resources/config'));
        $loader->load('merger-services.yaml');
        $this->assertArrayHasKey(
            MergerTranslationResourceProvider::class,
            $container->findTaggedServiceIds('derafu_translation.resource_provider')
        );
        $container->register('app.translator', Translator::class)
            ->setFactory([TranslatorFactory::class, 'create'])
            ->setArguments(['es', ['en'], new TaggedIteratorArgument('derafu_translation.resource_provider')]);
        foreach ($container->getDefinitions() as $definition) {
            $definition->setPublic(true);
        }
        $container->compile();

        return $container;
    }

    public function testConfigurationConstructsTheTranslationProvider(): void
    {
        $provider = $this->container()->get(MergerTranslationResourceProvider::class);
        $this->assertInstanceOf(MergerTranslationResourceProvider::class, $provider);
        $directories = [...$provider->getDirectories()];
        $this->assertCount(1, $directories);
        $this->assertSame(realpath(dirname(__DIR__, 2) . '/resources/translations'), realpath($directories[0]));
    }

    public function testTaggedResourcesTranslateARealInvalidSeparatorException(): void
    {
        $translator = $this->container()->get('app.translator');
        $this->assertInstanceOf(Translator::class, $translator);
        try {
            (new RawMerger())->merge(['first', 'second'], ['separator' => 42]);
            $this->fail('An invalid merge separator was accepted.');
        } catch (MergerException $exception) {
            $this->assertSame('El separador de unión debe ser un string; se recibió int.', $exception->trans($translator));
        }
    }
}
