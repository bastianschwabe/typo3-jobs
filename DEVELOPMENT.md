# Development environment

This repository carries a complete, disposable TYPO3 instance. Two commands and
you have a running site with demo jobs in two languages:

```bash
ddev start
```

```bash
ddev typo3-install
```

| | |
|---|---|
| Backend | `https://typo3-jobs.ddev.site/typo3` — **`test` / `test`** |
| Job list (EN) | `https://typo3-jobs.ddev.site/jobs` |
| Job list (DE) | `https://typo3-jobs.ddev.site/de/stellenangebote` |
| Job detail | `https://typo3-jobs.ddev.site/jobs/detail/<slug>` |

> The first `ddev start` on a fresh machine asks for your password once, to add
> the project hostname to `/etc/hosts`. Run it in an interactive terminal.

After the first install, a plain `ddev start` is enough — the database lives in
the DDEV volume and survives restarts.

---

## What lives where

The development setup is deliberately spread across three places, each with one
job:

```
.ddev/
  config.yaml                    PHP 8.5, MariaDB 10.11, docroot .Build/Web
  commands/web/typo3-install     the one command that rebuilds everything

config/
  sites/main/config.yaml         site: languages, base URLs, route enhancers
  sites/main/settings.yaml       site settings: storage folder, detail page, organization

Build/
  Icons/generate.py              draws Resources/Public/Icons from pixel grids
  TestSite/                      development-only TYPO3 extension (jobs_testsite)
  phpunit/                       PHPUnit configuration for both suites
  phpstan.neon                   static analysis, level 8
  .php-cs-fixer.php              coding standards (typo3/coding-standards)
```

Nothing in `Build/`, `Tests/` or `config/` ships with the extension — they are
listed in [`.gitattributes`](.gitattributes) as `export-ignore`, so `composer`
archives and TER uploads stay lean.

### `Build/TestSite` — the development site package

A real TYPO3 extension (`jobs_testsite`), wired in as a Composer `path`
repository and required under `require-dev`. It exists because the extension
alone cannot render a page: something has to provide the `PAGE` object, a
template and a menu.

```
Build/TestSite/
  composer.json                              path repository, require-dev only
  ext_localconf.php                          relaxes the backend password policy
  Classes/Command/SeedCommand.php            builds the page tree and demo records
  Configuration/Services.yaml                registers the command
  Configuration/Sets/TestSite/
    config.yaml                              depends on EXT:jobs + fluid_styled_content
    setup.typoscript                         PAGE / PAGEVIEW, menus, content
  Resources/Private/
    Pages/Default.html                       page template
    Layouts/Default.html                     HTML skeleton, navigation, demo CSS
```

The site set `bastianschwabe/jobs-testsite` is assigned in
`config/sites/main/config.yaml` and pulls in `bastianschwabe/jobs` through its
own dependencies, so the extension's TypoScript and template paths come along
automatically.

---

## What `ddev typo3-install` actually does

It is a **full reset**, not an update. Every step exists for a reason:

| Step | Why |
|---|---|
| `composer install` | keeps the vendor tree in sync with `composer.lock` |
| drop and recreate the database | guarantees the seed starts from an empty page tree |
| `rm -rf var/cache/*` | the TYPO3 cache identifier does not track TCA file contents, so a stale cache would make the seed write records against the *previous* schema |
| `rm -f config/system/settings.php` | forces a fresh encryption key and database credentials |
| `typo3 setup` | creates the schema from TCA plus `ext_tables.sql`, and the `test` backend user |
| `typo3 jobs:seed` | creates pages, plugins and demo records |
| `typo3 cache:flush` | clears what the seed invalidated |

`typo3 setup` runs **without** `--create-site`: the site configuration is
committed under `config/sites/main` and would otherwise be overwritten.

### The uid contract

`config/sites/main` hard-codes the page uids the seed produces:

| Page | uid | Referenced by |
|---|---|---|
| Home (site root) | 1 | `rootPageId` |
| Jobs (list plugin) | 2 | back link of the detail plugin |
| Job detail (detail plugin) | 3 | `jobs.detailPid` |
| Job records (storage folder) | 4 | `jobs.storagePid` |

This only holds on an empty database, which is why the seed refuses to run
otherwise and compares every uid against its expectation:

```
Page "NEWpageHome" became uid 7 but config/sites/main expects 1.
The database was not empty.
```

If you ever see that, you ran `jobs:seed` directly instead of
`ddev typo3-install`.

---

## Why a seed command and not a database dump

The whole page tree, both plugins and every demo record are created through
`DataHandler` in
[`Build/TestSite/Classes/Command/SeedCommand.php`](Build/TestSite/Classes/Command/SeedCommand.php).
Slugs, relations and translations therefore end up exactly as they would if an
editor had clicked them together — including the parts DataHandler does silently,
like copying `l10n_mode: exclude` fields into a translation.

A committed SQL dump would restore faster but would rot: every new field means a
re-export, and reviewing a change to the demo data means reading a binary diff.
The seed is a plain nested array that grows with the model and shows up properly
in `git diff`.

For a fast reset during a working session, take a snapshot right after the
install and restore that instead:

```bash
ddev snapshot --name jobs-clean
```

```bash
ddev snapshot restore jobs-clean
```

---

## Extending the demo data

Everything below happens in `SeedCommand::buildStructure()`, which returns one
DataHandler datamap. Records are keyed by a `NEW…` placeholder; using that
placeholder in a relation field is how records are wired together.

### Add a record

```php
$data['tx_jobs_domain_model_location']['NEWlocHamburg'] = [
    'pid' => $storage,
    'name' => 'Hamburg office',
    'city' => 'Hamburg',
    'address_country' => 'DE',
];
```

Then reference it from a job — comma-separate for multi-value relations:

```php
'locations' => 'NEWlocBerlin,NEWlocHamburg',
```

Order matters only in one direction: a record must appear in the array **before**
the record that points at it.

### Add a translation

Set the language and point at the default-language placeholder. Mind the field
name — it differs by table:

```php
'NEWlocHamburgDe' => [
    'pid' => $storage,
    'name' => 'Büro Hamburg',
    'sys_language_uid' => self::languageDe,
    'l10n_parent' => 'NEWlocHamburg',   // tt_content uses l18n_parent instead
],
```

Only translate what is genuinely editorial. Relations, dates and structured-data
values carry `l10n_mode: exclude` in TCA, and DataHandler copies those into the
translation by itself — listing them again would be noise at best and drift at
worst.

### Add a page

Add it to `$data['pages']`, then decide whether it needs a fixed uid. If the
site configuration has to reference it, add it to `SeedCommand::expectedPageUids`
so a wrong uid fails loudly instead of producing a site that points nowhere. New
pages are created hidden by TCA default; the seed switches the whole tree live in
one pass, so you do not need `'hidden' => 0` per entry.

Menu order comes from `SeedCommand::sortPages()`, because DataHandler places new
records at the top of their page and would otherwise reverse the navigation.

### Add a content element

Content elements are built in a **second** pass (`buildContent()`), once the page
uids are known, because the plugin FlexForms have to reference real pages:

```php
'NEWctList' => [
    'pid' => $jobs,
    'CType' => 'jobs_list',
    'pi_flexform' => $this->flexForm(['settings.detailPid' => (string)$detail]),
],
```

`flexForm()` writes the `sDEF` sheet only. If you need a second sheet, extend
that helper rather than hand-writing XML at the call site.

### Add a language

1. Add the language to `config/sites/main/config.yaml`.
2. Add a `self::languageXx` constant to `SeedCommand` and the translated records.
3. Add the matching `xx.locallang*.xlf` files under
   `Resources/Private/Language/`.

The German language is configured with `fallbackType: fallback`, so a record
without a translation still appears. That is deliberate: the *Working Student*
job is intentionally left untranslated to keep that path visible.

### After changing TCA or `ext_tables.sql`

Run `ddev typo3-install` again, not just a cache flush. Adding a column changes
the schema, and the seed has to write against the new one. The three tracking
tables (`tx_jobs_*_statistic`) live in `ext_tables.sql` only, so a change there
needs the same full reset.

### Trying the tracking

The tracking tables start empty. Two visits with `curl` are enough to fill the
statistics module and the dashboard widget:

```bash
curl -sk -c /tmp/jar -b /tmp/jar -o /dev/null https://typo3-jobs.ddev.site/jobs/detail/backend-developer-php-typo3
```

```bash
curl -sk -c /tmp/jar -b /tmp/jar -o /dev/null -w '%{http_code} -> %{redirect_url}\n' https://typo3-jobs.ddev.site/jobs/detail/backend-developer-php-typo3/apply
```

The cookie jar matters: without it every request is a new frontend session and
therefore a new visitor. Requests made while logged into the backend are not
counted at all.

---

## The password policy override

`test` / `test` does not satisfy TYPO3's default password policy (eight
characters, upper and lower case, a digit and a special character). The
development site package therefore replaces the policy with an empty validator
set in [`Build/TestSite/ext_localconf.php`](Build/TestSite/ext_localconf.php).

Three things keep that contained:

- the override only applies when `TYPO3_CONTEXT` is `Development`, which the
  DDEV configuration sets;
- `jobs_testsite` is a `require-dev` dependency, so it is never installed in a
  production deployment;
- `Build/` is `export-ignore`d, so it is not even part of the released package.

If you would rather not weaken it at all, change `ADMIN_PASSWORD` in
`.ddev/commands/web/typo3-install` to something policy-compliant and delete
`Build/TestSite/ext_localconf.php`.

---

## Tests

```bash
ddev composer ci
```

Runs, in order: PHP lint, coding standards, PHPStan level 8, unit tests,
functional tests. The individual steps:

| Command | Scope |
|---|---|
| `composer test:unit` | pure logic, mostly the JSON-LD builder |
| `composer test:functional` | TCA, schema, repository, registration, templates |
| `composer analyse` | PHPStan level 8 |
| `composer cs:fix` | apply coding standards |

Functional tests need **no Docker**. `Build/phpunit/FunctionalTests.xml`
defaults `typo3DatabaseDriver` to `pdo_sqlite`, so the suite runs straight from
the host.

PHPUnit does not overwrite an environment variable that already exists, which is
how the same file serves both worlds. To run the suite against MariaDB inside
the container:

```bash
ddev exec typo3DatabaseDriver=mysqli composer test:functional
```

That path uses the `root` credentials exported in `.ddev/config.yaml`, because
the testing framework creates its own throwaway databases (`db_ft1`, `db_ft2`,
…) and DDEV's `db` user is not allowed to `CREATE DATABASE`.

CI runs the suite on SQLite across PHP 8.2 to 8.5 and once more against
MariaDB.

### Adding functional fixtures

`Tests/Functional/Fixtures/jobs.csv` is a TYPO3 testing-framework data set: one
block per table, a header row prefixed with a comma, then the rows. `\NULL`
writes a real `NULL`, which is what distinguishes a job that never expires from
one that expired in 1970.

Two things to keep in mind:

- Do not open a second block for a table that already has one — add the column
  to the existing header instead. The parser silently mismatches otherwise.
- The CSV bypasses DataHandler. Fields that DataHandler would copy into a
  translation must be written into the fixture by hand, or the row will not look
  like a record the backend produced.

---

## Releasing

A release is a Git tag. `.github/workflows/release.yml` picks up every tag that
looks like a version number and publishes the tagged commit to the TER through
[Tailor](https://github.com/TYPO3/tailor); Packagist follows the tag by itself
once its GitHub hook is set up.

1. Bump the version in **three** places: `ext_emconf.php`, `composer.json`
   (`extra.typo3/cms.version`) and `Documentation/guides.xml` (`release`, and
   `version` for the major.minor part). The workflow refuses a tag whose number
   differs from any of them.
2. Move the `unreleased` entry in `CHANGELOG.md` to the version and date.
3. Tag with a message — it becomes the TER upload comment:

```bash
git tag -a 0.1.0 -m "First public version"
```

```bash
git push origin main --tags
```

The workflow needs the repository secret `TYPO3_API_TOKEN`, an access token of
the TER account that owns the extension key (extensions.typo3.org > My
Extensions > Access Tokens, scopes `extension:read` and `extension:write`).

To check what the TER will receive without uploading anything, build the
artefact locally with Tailor and list it:

```bash
TYPO3_EXCLUDE_FROM_PACKAGING="$PWD/Build/Tailor/ExcludeFromPackaging.php" tailor create-artefact 0.1.0 jobs && unzip -l tailor-version-artefact/jobs_0.1.0.zip
```

Tailor does **not** read `.gitattributes`. It uses
`Build/Tailor/ExcludeFromPackaging.php`, which repeats Tailor's own defaults
(`Build/`, `Tests/`, `.ddev/`, `.github/`, tooling files) and adds what is
specific to this repository (`config/`, `DEVELOPMENT.md`, cache files). A custom
list replaces the defaults instead of extending them, so keep both halves in
that file. The `export-ignore` entries in `.gitattributes` only shape
`git archive` and the Composer package.

`ext_emconf.php` must stay free of `declare(strict_types=1)`. The TER parses
the file with its own reader and rejects the whole upload otherwise; the release
workflow checks for it before uploading.

The documentation renders in CI with the official renderer. Locally:

```bash
docker run --rm -v "$PWD":/project ghcr.io/typo3-documentation/render-guides:latest --config=Documentation --fail-on-log
```

---

## Things that bit us, so they do not bite you

Each of these cost a debugging round and now has a regression test:

| Symptom | Cause |
|---|---|
| Geo coordinates rounded to ~1 km | TCA `number` / `decimal` is hard-wired to two decimals in `DataHandler`. The two columns come from `ext_tables.sql` instead. |
| German jobs lost their location and company | Relations were translatable. They now carry `l10n_mode: exclude`. |
| German content elements appeared twice | `tt_content` uses `l18n_parent`, not `l10n_parent`. The wrong key is ignored without a warning. |
| Every filter URL returned 404 | A `GET` form cannot produce a `cHash`. The list renders as `USER_INT` and its parameters are excluded from the cache hash. |
| Filters returned an empty list | Extbase refuses to map properties onto a non-entity argument until they are allow-listed in `initializeListAction()`. |
| Seed wrote against the old schema | `var/cache` was not cleared before `typo3 setup`. |
| Every functional test died in `Configuration/Services.php` | `ExtensionManagementUtility::isLoaded()` is not available while the DI container is built. The dashboard widget is registered behind an `interface_exists()` check instead. |
| `typo3 setup` failed with "no argument named $configuration" | The dashboard's compiler pass injects `WidgetConfigurationInterface $configuration` by name; every widget constructor has to accept it. |
| Job views were never counted | The detail plugin is cached, so its controller only runs on a cache miss. Views are counted by the `JobViewTracker` middleware, which sees every request. |
| Middleware order test asserted the wrong direction | `MiddlewareStackResolver::resolve()` returns the stack reversed: the dispatcher runs the last entry first, so "after" means a lower index. |
| TER upload failed with "Details could not be extracted from the provided file" | `ext_emconf.php` carried `declare(strict_types=1)`, which the TER's emconf reader cannot parse. Removed; the release workflow now refuses to upload if it comes back. |
