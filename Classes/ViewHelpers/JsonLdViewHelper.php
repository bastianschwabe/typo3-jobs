<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Renders a structured data array as a JSON-LD script tag.
 *
 * Angle brackets and ampersands are escaped as unicode sequences so that the
 * job description, which is editor-supplied HTML, cannot terminate the script
 * element.
 *
 * <jobs:jsonLd data="{schema}" />
 */
final class JsonLdViewHelper extends AbstractViewHelper
{
    /** The tag itself is the output, so Fluid must not escape it. */
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('data', 'array', 'Structured data to encode', true);
    }

    public function render(): string
    {
        $data = $this->arguments['data'];
        if (!is_array($data) || $data === []) {
            return '';
        }

        $json = json_encode(
            $data,
            \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_HEX_TAG | \JSON_HEX_AMP,
        );

        if ($json === false) {
            return '';
        }

        return '<script type="application/ld+json">' . $json . '</script>';
    }
}
