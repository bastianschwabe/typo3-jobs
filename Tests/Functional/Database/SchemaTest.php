<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Tests\Functional\Database;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Every record table, column and MM table is derived from TCA by the Core;
 * ext_tables.sql only adds the two coordinate columns and the three tracking
 * tables, which deliberately have no TCA. These tests keep that split honest.
 */
final class SchemaTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['bastianschwabe/jobs'];

    /**
     * @return array<string, array{0: string}>
     */
    public static function tableNameProvider(): array
    {
        $tables = [
            'tx_jobs_domain_model_job',
            'tx_jobs_domain_model_level',
            'tx_jobs_domain_model_occupational_field',
            'tx_jobs_domain_model_location',
            'tx_jobs_domain_model_contact',
            'tx_jobs_domain_model_company_profile',
            'tx_jobs_job_location_mm',
            'tx_jobs_job_contact_mm',
            'tx_jobs_job_company_profile_mm',
        ];

        return array_combine($tables, array_map(static fn(string $t): array => [$t], $tables));
    }

    #[Test]
    #[DataProvider('tableNameProvider')]
    public function tableIsCreatedFromTcaAlone(string $tableName): void
    {
        $schemaManager = $this->get(ConnectionPool::class)
            ->getConnectionForTable($tableName)
            ->createSchemaManager();

        self::assertTrue(
            $schemaManager->tablesExist([$tableName]),
            sprintf('Table "%s" was not created from TCA.', $tableName),
        );
    }

    /**
     * @return array<string, array{0: string, 1: list<string>}>
     */
    public static function trackingTableProvider(): array
    {
        return [
            'views' => ['tx_jobs_view_statistic', ['job', 'session_hash', 'counter']],
            'applications' => ['tx_jobs_apply_statistic', ['job', 'session_hash', 'type', 'status']],
            'filters' => ['tx_jobs_filter_statistic', ['session_hash', 'filter_hash', 'level', 'search', 'result_count']],
        ];
    }

    /**
     * @param list<string> $expectedColumns
     */
    #[Test]
    #[DataProvider('trackingTableProvider')]
    public function trackingTableComesFromExtTablesSqlWithoutTca(string $tableName, array $expectedColumns): void
    {
        self::assertArrayNotHasKey($tableName, $GLOBALS['TCA'], 'Tracking tables must not carry TCA.');

        $columns = array_keys(
            $this->get(ConnectionPool::class)
                ->getConnectionForTable($tableName)
                ->createSchemaManager()
                ->listTableColumns($tableName),
        );

        foreach ($expectedColumns as $column) {
            self::assertContains($column, $columns, sprintf('Column "%s" is missing in %s.', $column, $tableName));
        }
    }

    #[Test]
    public function oneViewRowPerSessionAndJobIsEnforcedByTheDatabase(): void
    {
        $indexes = $this->get(ConnectionPool::class)
            ->getConnectionForTable('tx_jobs_view_statistic')
            ->createSchemaManager()
            ->listTableIndexes('tx_jobs_view_statistic');

        $uniqueColumns = [];
        foreach ($indexes as $index) {
            if ($index->isUnique() && !$index->isPrimary()) {
                $uniqueColumns[] = $index->getColumns();
            }
        }

        self::assertContains(['job', 'session_hash'], $uniqueColumns);
    }

    #[Test]
    public function jobTableCarriesEveryStructuredDataColumn(): void
    {
        $columns = $this->get(ConnectionPool::class)
            ->getConnectionForTable('tx_jobs_domain_model_job')
            ->createSchemaManager()
            ->listTableColumns('tx_jobs_domain_model_job');

        $columnNames = array_keys($columns);

        $expected = [
            'title', 'slug', 'teaser', 'description', 'date_posted', 'valid_through',
            'employment_type', 'education_category', 'education_requirements',
            'experience_months', 'experience_in_place_of_education', 'experience_requirements',
            'industry', 'occupational_category', 'identifier', 'job_location_type',
            'applicant_location_requirements', 'direct_apply', 'application_url',
            'application_email', 'base_salary_currency', 'base_salary_unit',
            'base_salary_value', 'base_salary_min', 'base_salary_max',
            'level', 'occupational_field', 'locations', 'contacts', 'company_profiles',
            'images', 'downloads',
        ];

        foreach ($expected as $column) {
            self::assertContains($column, $columnNames, sprintf('Column "%s" is missing.', $column));
        }
    }

    #[Test]
    public function systemColumnsAreDerivedFromCtrl(): void
    {
        $columns = $this->get(ConnectionPool::class)
            ->getConnectionForTable('tx_jobs_domain_model_job')
            ->createSchemaManager()
            ->listTableColumns('tx_jobs_domain_model_job');

        $columnNames = array_keys($columns);

        foreach (['hidden', 'starttime', 'endtime', 'sys_language_uid', 'l10n_parent', 'l10n_source'] as $column) {
            self::assertContains($column, $columnNames, sprintf('System column "%s" is missing.', $column));
        }
    }

    /**
     * TCA type "number" with format "decimal" is fixed at two decimals, which
     * is about a kilometre of error. The coordinates therefore get their column
     * from ext_tables.sql, and that has to stay that way.
     */
    #[Test]
    public function coordinatesKeepEightDecimals(): void
    {
        $columns = $this->get(ConnectionPool::class)
            ->getConnectionForTable('tx_jobs_domain_model_location')
            ->createSchemaManager()
            ->listTableColumns('tx_jobs_domain_model_location');

        foreach (['latitude', 'longitude'] as $column) {
            self::assertSame(8, $columns[$column]->getScale(), sprintf('Column "%s" lost its precision.', $column));
        }
    }

    #[Test]
    public function validThroughIsNullableSoOpenEndedJobsNeverExpire(): void
    {
        $columns = $this->get(ConnectionPool::class)
            ->getConnectionForTable('tx_jobs_domain_model_job')
            ->createSchemaManager()
            ->listTableColumns('tx_jobs_domain_model_job');

        self::assertFalse($columns['valid_through']->getNotnull());
    }
}
