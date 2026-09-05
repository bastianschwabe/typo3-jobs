<?php

declare(strict_types=1);

namespace BastianSchwabe\JobsTestSite\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Builds the development page tree, plugins and demo records.
 *
 * Everything goes through DataHandler, so slugs, relations and translations end
 * up exactly as they would when an editor clicks them together. The command
 * expects an empty page tree — `ddev typo3-install` wipes the database first.
 */
#[AsCommand(name: 'jobs:seed', description: 'Seed the development instance with demo pages, plugins and job records.')]
final class SeedCommand extends Command
{
    /**
     * Uids the committed site configuration and settings rely on. The command
     * fails rather than silently producing a site config that points nowhere.
     */
    private const expectedPageUids = [
        'NEWpageHome' => 1,
        'NEWpageJobs' => 2,
        'NEWpageDetail' => 3,
        'NEWpageRecords' => 4,
    ];

    private const languageDe = 1;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        Bootstrap::initializeBackendAuthentication();

        if ($this->pageCount() > 0) {
            $io->error('The page tree is not empty. Run "ddev typo3-install" to rebuild the instance from scratch.');

            return Command::FAILURE;
        }

        $structure = $this->runDataHandler($this->buildStructure(), $io);
        if ($structure === null) {
            return Command::FAILURE;
        }

        foreach (self::expectedPageUids as $placeholder => $expected) {
            $actual = (int)($structure[$placeholder] ?? 0);
            if ($actual !== $expected) {
                $io->error(sprintf(
                    'Page "%s" became uid %d but config/sites/main expects %d. The database was not empty.',
                    $placeholder,
                    $actual,
                    $expected,
                ));

                return Command::FAILURE;
            }
        }

        $this->sortPages($structure);

        if ($this->runDataHandler($this->buildContent($structure), $io) === null) {
            return Command::FAILURE;
        }

        $io->success('Demo content created.');
        $io->definitionList(
            ['Home' => '/'],
            ['Job list' => '/jobs'],
            ['Job detail' => '/jobs/detail/<slug>'],
            ['Record storage' => 'page ' . self::expectedPageUids['NEWpageRecords']],
            ['German' => '/de/'],
        );

        return Command::SUCCESS;
    }

    /**
     * Pages, taxonomy, locations, contacts, company and jobs in one datamap.
     * DataHandler resolves NEW placeholders used in relation fields, so the
     * whole graph can be described in a single declarative array.
     *
     * @return array<string, array<string, array<string, mixed>>>
     */
    private function buildStructure(): array
    {
        $day = 86400;
        $today = (new \DateTimeImmutable('today'))->getTimestamp();

        $data = [];

        $data['pages'] = [
            'NEWpageHome' => [
                'pid' => 0,
                'title' => 'Jobs Demo',
                'slug' => '/',
                'doktype' => 1,
                'is_siteroot' => 1,
            ],
            'NEWpageJobs' => [
                'pid' => 'NEWpageHome',
                'title' => 'Jobs',
                'slug' => '/jobs',
                'doktype' => 1,
            ],
            'NEWpageDetail' => [
                'pid' => 'NEWpageJobs',
                'title' => 'Job detail',
                'slug' => '/jobs/detail',
                'doktype' => 1,
                'nav_hide' => 1,
            ],
            'NEWpageRecords' => [
                'pid' => 'NEWpageHome',
                'title' => 'Job records',
                'slug' => '/job-records',
                'doktype' => 254,
            ],
            // German page tree
            'NEWpageHomeDe' => [
                'pid' => 0,
                'title' => 'Jobs Demo',
                'slug' => '/',
                'sys_language_uid' => self::languageDe,
                'l10n_parent' => 'NEWpageHome',
            ],
            'NEWpageJobsDe' => [
                'pid' => 'NEWpageHome',
                'title' => 'Stellenangebote',
                'slug' => '/stellenangebote',
                'sys_language_uid' => self::languageDe,
                'l10n_parent' => 'NEWpageJobs',
            ],
            'NEWpageDetailDe' => [
                'pid' => 'NEWpageJobs',
                'title' => 'Stellendetail',
                'slug' => '/stellenangebote/detail',
                'nav_hide' => 1,
                'sys_language_uid' => self::languageDe,
                'l10n_parent' => 'NEWpageDetail',
            ],
        ];

        // TYPO3 creates new pages hidden by default. The demo tree is meant to
        // be browsable straight after the install, so switch them all live.
        foreach (array_keys($data['pages']) as $identifier) {
            $data['pages'][$identifier]['hidden'] = 0;
        }

        $storage = 'NEWpageRecords';

        $data['tx_jobs_domain_model_level'] = [
            'NEWlevelJunior' => ['pid' => $storage, 'title' => 'Junior', 'months_of_experience' => 0],
            'NEWlevelPro' => ['pid' => $storage, 'title' => 'Professional', 'months_of_experience' => 24],
            'NEWlevelSenior' => ['pid' => $storage, 'title' => 'Senior', 'months_of_experience' => 60],
            'NEWlevelJuniorDe' => [
                'pid' => $storage,
                'title' => 'Berufseinstieg',
                'sys_language_uid' => self::languageDe,
                'l10n_parent' => 'NEWlevelJunior',
            ],
            'NEWlevelSeniorDe' => [
                'pid' => $storage,
                'title' => 'Erfahren',
                'sys_language_uid' => self::languageDe,
                'l10n_parent' => 'NEWlevelSenior',
            ],
        ];

        $data['tx_jobs_domain_model_occupational_field'] = [
            'NEWfieldDev' => [
                'pid' => $storage,
                'title' => 'Software Development',
                'industry' => 'Information Technology',
                'occupational_category' => '15-1252.00',
            ],
            'NEWfieldMarketing' => [
                'pid' => $storage,
                'title' => 'Marketing',
                'industry' => 'Marketing and Advertising',
                'occupational_category' => '11-2021.00',
            ],
            'NEWfieldDevDe' => [
                'pid' => $storage,
                'title' => 'Softwareentwicklung',
                'sys_language_uid' => self::languageDe,
                'l10n_parent' => 'NEWfieldDev',
            ],
        ];

        $data['tx_jobs_domain_model_location'] = [
            'NEWlocBerlin' => [
                'pid' => $storage,
                'name' => 'Berlin headquarters',
                'street' => 'Beispielstraße 1',
                'zip' => '10115',
                'city' => 'Berlin',
                'address_region' => 'Berlin',
                'address_country' => 'DE',
                'latitude' => 52.5200,
                'longitude' => 13.4050,
                'phone' => '+49 30 1234567',
                'email' => 'berlin@example.org',
            ],
            'NEWlocMunich' => [
                'pid' => $storage,
                'name' => 'Munich office',
                'street' => 'Musterweg 42',
                'zip' => '80331',
                'city' => 'München',
                'address_region' => 'Bayern',
                'address_country' => 'DE',
                'latitude' => 48.1372,
                'longitude' => 11.5756,
            ],
        ];

        $data['tx_jobs_domain_model_contact'] = [
            'NEWcontactAnna' => [
                'pid' => $storage,
                'first_name' => 'Anna',
                'last_name' => 'Beispiel',
                'role' => 'Head of People',
                'description' => 'Answers everything about the application process.',
                'phone' => '+49 30 1234500',
                'email' => 'anna.beispiel@example.org',
            ],
        ];

        $data['tx_jobs_domain_model_company_profile'] = [
            'NEWcompany' => [
                'pid' => $storage,
                'title' => 'Example Company GmbH',
                'legal_name' => 'Example Company Gesellschaft mit beschränkter Haftung',
                'teaser' => 'A fictional company used by the development instance.',
                'description' => '<p>Example Company builds fictional products for demonstration purposes.</p>',
                'url_website' => 'https://www.example.org/',
                'url_linkedin' => 'https://www.linkedin.com/company/example/',
                'location' => 'NEWlocBerlin',
                'number_of_employees' => 120,
            ],
        ];

        $data['tx_jobs_domain_model_job'] = [
            // Full record: locations, salary range, expiry, identifier.
            'NEWjobBackend' => [
                'pid' => $storage,
                'title' => 'Backend Developer (PHP/TYPO3)',
                'teaser' => 'Build and maintain TYPO3 extensions for our platform.',
                'description' => '<p>You work on our TYPO3 platform and its extensions.</p>'
                    . '<ul><li>Develop TYPO3 extensions</li><li>Review code</li></ul>',
                'date_posted' => $today - 7 * $day,
                'valid_through' => $today + 60 * $day,
                'employment_type' => 'FULL_TIME',
                'level' => 'NEWlevelSenior',
                'occupational_field' => 'NEWfieldDev',
                'locations' => 'NEWlocBerlin',
                'contacts' => 'NEWcontactAnna',
                'company_profiles' => 'NEWcompany',
                'identifier' => 'JOB-2026-001',
                'education_category' => 'bachelor degree',
                'education_requirements' => 'A degree in computer science or comparable experience.',
                'experience_requirements' => 'Several years of professional TYPO3 work.',
                'base_salary_currency' => 'EUR',
                'base_salary_unit' => 'YEAR',
                'base_salary_min' => 60000.0,
                'base_salary_max' => 75000.0,
                'direct_apply' => 0,
                'application_email' => 'jobs@example.org',
            ],
            // Fixed salary, second location, two employment types.
            'NEWjobMarketing' => [
                'pid' => $storage,
                'title' => 'Marketing Manager',
                'teaser' => 'Own our campaigns from idea to reporting.',
                'description' => '<p>You plan and run our marketing campaigns.</p>',
                'date_posted' => $today - 3 * $day,
                'valid_through' => null,
                'employment_type' => 'FULL_TIME,PART_TIME',
                'level' => 'NEWlevelPro',
                'occupational_field' => 'NEWfieldMarketing',
                'locations' => 'NEWlocMunich',
                'company_profiles' => 'NEWcompany',
                'identifier' => 'JOB-2026-002',
                'base_salary_currency' => 'EUR',
                'base_salary_unit' => 'MONTH',
                'base_salary_value' => 4800.0,
                'application_url' => 'https://www.example.org/apply',
            ],
            // Fully remote: no location at all, so the TELECOMMUTE path is exercised.
            'NEWjobRemote' => [
                'pid' => $storage,
                'title' => 'Remote UX Designer',
                'teaser' => 'Design interfaces from wherever you are.',
                'description' => '<p>You design our product interfaces.</p>',
                'date_posted' => $today - 1 * $day,
                'valid_through' => null,
                'employment_type' => 'PART_TIME',
                'level' => 'NEWlevelPro',
                'occupational_field' => 'NEWfieldDev',
                'company_profiles' => 'NEWcompany',
                'job_location_type' => 1,
                'applicant_location_requirements' => 'DE,AT,CH',
                'identifier' => 'JOB-2026-003',
                'direct_apply' => 0,
                'application_email' => 'jobs@example.org',
            ],
            // Deliberately left untranslated, to exercise the language fallback.
            'NEWjobWorking' => [
                'pid' => $storage,
                'title' => 'Working Student Frontend',
                'teaser' => 'Support our frontend team while you study.',
                'description' => '<p>You help our frontend team two days a week.</p>',
                'date_posted' => $today - 14 * $day,
                'valid_through' => $today + 10 * $day,
                'employment_type' => 'PART_TIME',
                'level' => 'NEWlevelJunior',
                'occupational_field' => 'NEWfieldDev',
                'locations' => 'NEWlocBerlin',
                'company_profiles' => 'NEWcompany',
                'identifier' => 'JOB-2026-004',
            ],
            'NEWjobBackendDe' => [
                'pid' => $storage,
                'title' => 'Backend-Entwickler:in (PHP/TYPO3)',
                'teaser' => 'Du entwickelst und pflegst TYPO3-Extensions für unsere Plattform.',
                'description' => '<p>Du arbeitest an unserer TYPO3-Plattform und ihren Extensions.</p>'
                    . '<ul><li>TYPO3-Extensions entwickeln</li><li>Code reviewen</li></ul>',
                'education_requirements' => 'Ein Studium der Informatik oder vergleichbare Erfahrung.',
                'experience_requirements' => 'Mehrjährige Berufserfahrung mit TYPO3.',
                'sys_language_uid' => self::languageDe,
                'l10n_parent' => 'NEWjobBackend',
            ],
            'NEWjobRemoteDe' => [
                'pid' => $storage,
                'title' => 'UX Designer:in (remote)',
                'teaser' => 'Gestalte Oberflächen von überall aus.',
                'description' => '<p>Du gestaltest unsere Produktoberflächen.</p>',
                'sys_language_uid' => self::languageDe,
                'l10n_parent' => 'NEWjobRemote',
            ],
        ];

        return $data;
    }

    /**
     * Content elements, built once the page uids are known so the FlexForms can
     * reference them.
     *
     * @param array<string, string> $structure
     * @return array<string, array<string, array<string, mixed>>>
     */
    private function buildContent(array $structure): array
    {
        $home = (int)$structure['NEWpageHome'];
        $jobs = (int)$structure['NEWpageJobs'];
        $detail = (int)$structure['NEWpageDetail'];

        return [
            'tt_content' => [
                'NEWctIntro' => [
                    'pid' => $home,
                    'CType' => 'text',
                    'header' => 'TYPO3 jobs development instance',
                    'bodytext' => '<p>Demo data for EXT:jobs. Follow "Jobs" for the list plugin.</p>',
                ],
                'NEWctIntroDe' => [
                    'pid' => $home,
                    'CType' => 'text',
                    'header' => 'TYPO3-Jobs-Entwicklungsinstanz',
                    'bodytext' => '<p>Demodaten für EXT:jobs. Unter „Stellenangebote" liegt das Listen-Plugin.</p>',
                    'sys_language_uid' => self::languageDe,
                    // tt_content uses l18n_parent, not l10n_parent.
                    'l18n_parent' => 'NEWctIntro',
                ],
                'NEWctList' => [
                    'pid' => $jobs,
                    'CType' => 'jobs_list',
                    'header' => 'Open positions',
                    'pi_flexform' => $this->flexForm(['settings.detailPid' => (string)$detail]),
                ],
                'NEWctListDe' => [
                    'pid' => $jobs,
                    'CType' => 'jobs_list',
                    'header' => 'Offene Stellen',
                    'pi_flexform' => $this->flexForm(['settings.detailPid' => (string)$detail]),
                    'sys_language_uid' => self::languageDe,
                    'l18n_parent' => 'NEWctList',
                ],
                'NEWctShow' => [
                    'pid' => $detail,
                    'CType' => 'jobs_show',
                    'pi_flexform' => $this->flexForm(['settings.listPid' => (string)$jobs]),
                ],
                'NEWctShowDe' => [
                    'pid' => $detail,
                    'CType' => 'jobs_show',
                    'pi_flexform' => $this->flexForm(['settings.listPid' => (string)$jobs]),
                    'sys_language_uid' => self::languageDe,
                    'l18n_parent' => 'NEWctShow',
                ],
            ],
        ];
    }

    /**
     * @param array<string, string> $fields
     */
    private function flexForm(array $fields): string
    {
        $xml = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>' . "\n"
            . '<T3FlexForms><data><sheet index="sDEF"><language index="lDEF">';
        foreach ($fields as $name => $value) {
            $xml .= sprintf(
                '<field index="%s"><value index="vDEF">%s</value></field>',
                htmlspecialchars($name, \ENT_QUOTES | \ENT_XML1),
                htmlspecialchars($value, \ENT_QUOTES | \ENT_XML1),
            );
        }

        return $xml . '</language></sheet></data></T3FlexForms>';
    }

    /**
     * @param array<string, array<string, array<string, mixed>>> $data
     * @return array<string, string>|null substituted NEW ids, or null on failure
     */
    private function runDataHandler(array $data, SymfonyStyle $io): ?array
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($data, []);
        $dataHandler->process_datamap();

        if ($dataHandler->errorLog !== []) {
            $io->error('DataHandler reported problems:');
            $io->listing($dataHandler->errorLog);

            return null;
        }

        return $dataHandler->substNEWwithIDs;
    }

    /**
     * DataHandler places new records at the top of their page, which reverses
     * the menu. Sorting is rewritten so the tree matches the array above.
     *
     * @param array<string, string> $structure
     */
    private function sortPages(array $structure): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages');
        $order = ['NEWpageJobs', 'NEWpageRecords', 'NEWpageJobsDe'];

        foreach (array_values($order) as $index => $placeholder) {
            if (!isset($structure[$placeholder])) {
                continue;
            }
            $connection->update(
                'pages',
                ['sorting' => ($index + 1) * 256],
                ['uid' => (int)$structure[$placeholder]],
            );
        }
    }

    private function pageCount(): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();

        return (int)$queryBuilder->count('uid')->from('pages')->executeQuery()->fetchOne();
    }
}
