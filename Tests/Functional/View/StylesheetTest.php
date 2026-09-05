<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Tests\Functional\View;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Site\Set\SetRegistry;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The Fluid layout picks a stylesheet by the site setting jobs.stylesheet.
 * These tests keep the three halves in sync: the setting definition, the
 * files it points at, and the layout that loads them.
 */
final class StylesheetTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['bastianschwabe/jobs'];

    // The set depends on the fluid_styled_content set; without it the registry
    // drops the whole set as unresolvable.
    protected array $coreExtensionsToLoad = ['fluid_styled_content'];

    #[Test]
    public function siteSetDefinesTheStylesheetSetting(): void
    {
        $set = $this->get(SetRegistry::class)->getSet('bastianschwabe/jobs');
        self::assertNotNull($set);

        // The registry keeps the definitions as a plain list; the enum is
        // normalized to a value => label map.
        $definition = null;
        foreach ($set->settingsDefinitions as $candidate) {
            if ($candidate->key === 'jobs.stylesheet') {
                $definition = $candidate;
            }
        }
        self::assertNotNull($definition, 'Setting jobs.stylesheet is missing from the set.');
        self::assertSame('default', $definition->default);
        self::assertSame(['default', 'basic', 'none'], array_keys($definition->enum));
    }

    #[Test]
    public function settingIsMappedIntoThePluginSettings(): void
    {
        $setup = file_get_contents(dirname(__DIR__, 3) . '/Configuration/Sets/Jobs/setup.typoscript');
        self::assertIsString($setup);

        self::assertMatchesRegularExpression('/^\s*stylesheet = \{\$jobs\.stylesheet\}$/m', $setup);
    }

    #[Test]
    public function layoutLoadsAnExistingStylesheetForEveryVariant(): void
    {
        $root = dirname(__DIR__, 3);
        $layout = file_get_contents($root . '/Resources/Private/Layouts/Default.html');
        self::assertIsString($layout);

        preg_match_all('/href="EXT:jobs\/(Resources\/Public\/Css\/[A-Za-z]+\.css)"/', $layout, $matches);
        self::assertCount(2, $matches[1], 'Expected one stylesheet for "default" and one for "basic".');

        foreach ($matches[1] as $relativePath) {
            self::assertFileExists($root . '/' . $relativePath);
        }

        self::assertStringContainsString('<f:case value="none">', $layout);
        self::assertStringContainsString('<f:case value="basic">', $layout);
    }

    #[Test]
    public function bothStylesheetsAreLabelledInTheSet(): void
    {
        $definitions = Yaml::parseFile(dirname(__DIR__, 3) . '/Configuration/Sets/Jobs/settings.definitions.yaml');
        self::assertIsArray($definitions);
        self::assertArrayHasKey('jobs.stylesheet', $definitions['settings']);

        foreach (['labels.xlf', 'de.labels.xlf'] as $file) {
            $xliff = file_get_contents(dirname(__DIR__, 3) . '/Configuration/Sets/Jobs/' . $file);
            self::assertIsString($xliff);
            self::assertStringContainsString('id="settings.jobs.stylesheet"', $xliff, $file);
            self::assertStringContainsString('id="settings.description.jobs.stylesheet"', $xliff, $file);
        }
    }
}
