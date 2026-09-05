<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Tca;

use TYPO3\CMS\Core\Country\CountryProvider;

/**
 * Fills a multi-value select with ISO 3166-1 alpha-2 countries.
 *
 * TCA type "country" only supports a single value, but schema.org
 * applicantLocationRequirements may list several countries.
 */
final readonly class CountryItemsProvider
{
    public function __construct(private CountryProvider $countryProvider) {}

    /**
     * @param array{items: list<array{label: string, value: string}>} $parameters
     */
    public function items(array &$parameters): void
    {
        $countries = $this->countryProvider->getAll();
        foreach ($countries as $country) {
            $parameters['items'][] = [
                'label' => $country->getLocalizedNameLabel(),
                'value' => $country->getAlpha2IsoCode(),
            ];
        }
    }
}
