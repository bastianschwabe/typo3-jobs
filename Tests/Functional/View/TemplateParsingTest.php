<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Tests\Functional\View;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Parses every shipped Fluid file. Fluid validates ViewHelper arguments while
 * parsing, so this catches typos and removed arguments without rendering a
 * full frontend.
 */
final class TemplateParsingTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['bastianschwabe/jobs'];

    /**
     * @return array<string, array{0: string}>
     */
    public static function fluidFileProvider(): array
    {
        $root = dirname(__DIR__, 3) . '/Resources/Private';
        $files = [];

        foreach (['Layouts', 'Templates', 'Partials'] as $directory) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($root . '/' . $directory),
            );
            foreach ($iterator as $file) {
                if ($file instanceof \SplFileInfo && $file->getExtension() === 'html') {
                    $relative = substr($file->getPathname(), strlen($root) + 1);
                    $files[$relative] = [$file->getPathname()];
                }
            }
        }

        ksort($files);

        return $files;
    }

    #[Test]
    #[DataProvider('fluidFileProvider')]
    public function fluidFileParsesWithoutError(string $path): void
    {
        $renderingContext = $this->get(RenderingContextFactory::class)->create();
        $source = file_get_contents($path);
        self::assertIsString($source);

        $parsedTemplate = $renderingContext->getTemplateParser()->parse($source);

        self::assertNotNull($parsedTemplate->getRootNode());
    }

    /**
     * Fluid renders selected="" for a false condition in a plain HTML tag, and
     * browsers treat the mere presence of a boolean attribute as true. Such
     * attributes have to be emitted as bare tokens ({f:if(..., then: 'selected')})
     * outside a quoted attribute value.
     */
    #[Test]
    #[DataProvider('fluidFileProvider')]
    public function booleanAttributesAreNotBoundToConditions(string $path): void
    {
        $source = file_get_contents($path);
        self::assertIsString($source);

        self::assertDoesNotMatchRegularExpression(
            '/\b(selected|checked|disabled|required|readonly|multiple|hidden)\s*=\s*"\s*\{/i',
            $source,
            'Boolean HTML attributes must not be bound to a Fluid expression; see ' . basename($path),
        );
    }
}
