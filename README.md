# Jobs — job postings for TYPO3 v14

[![TYPO3 14](https://img.shields.io/badge/TYPO3-14.3-orange.svg)](https://get.typo3.org/version/14)
[![PHP](https://img.shields.io/badge/PHP-8.2%20–%208.5-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-GPL--2.0--or--later-green.svg)](LICENSE)

A filterable job list, a detail view, and `schema.org/JobPosting` structured data
that passes Google's Rich Results Test — without an extra mapping layer.

## Why another jobs extension

Most job extensions bolt structured data on afterwards and then fight the
vocabulary: an `employmentType` select that stores `Vollzeit` cannot become
`FULL_TIME` without a translation table, and `educationRequirements` free text is
silently ignored by Google.

Here the data model *is* the vocabulary. The select values are PHP backed enums
whose backing values are the literal, case-sensitive schema.org values, and the
TCA items are generated from those enums. A wrong value is structurally
impossible rather than caught in review.

## Requirements

| | |
|---|---|
| TYPO3 | 14.3 LTS or newer |
| PHP | 8.2 – 8.5 |

## Installation

```bash
composer require bastianschwabe/jobs
```

Then assign the **Jobs** site set to your site and point it at a storage folder.

## Quick start

1. Create a storage folder and add records: *Level*, *Field of work*,
   *Location*, *Contact person*, *Company profile*, then *Job*.
2. Add the **Jobs: List** plugin to your overview page and the
   **Jobs: Detail** plugin to a detail page.
3. In the site settings, set the detail page and — important — the fallback
   organization, so a job without a company profile still emits a valid
   `hiringOrganization`.

## Frontend

The plugins ship with a finished design: system fonts, neutral grays, no
external assets, so it blends into most sites as it is. Every color, radius
and the font are CSS custom properties on the `.jobs` wrapper:

```css
.jobs { --jobs-font-family: inherit; --jobs-color-accent: #0a3d62; --jobs-radius: 0; }
```

The site setting **Stylesheet** (`jobs.stylesheet`) switches between
`default`, `basic` (structure only, no colors or rounded corners) and `none`.
The markup and its BEM class names stay the same in all three modes.

## Structured data

The detail view emits a single `<script type="application/ld+json">` block.
Optional properties are omitted rather than emitted empty, because Google treats
an empty value as malformed while a missing one is merely absent.

Covered properties:

- **Required:** `title`, `description`, `datePosted`, `hiringOrganization`, `jobLocation`
- **Recommended:** `validThrough`, `employmentType`, `baseSalary`, `identifier`,
  `directApply`, `educationRequirements`, `experienceRequirements`, `industry`,
  `occupationalCategory`, `image`, `url`
- **Remote work:** `jobLocationType: TELECOMMUTE` plus `applicantLocationRequirements`

Some values are inherited so editors do not repeat themselves: a job without
`experienceMonths` takes them from its *Level*, and `industry` /
`occupationalCategory` fall back to its *Field of work*.

Validate your output with the
[Rich Results Test](https://search.google.com/test/rich-results) and the
[Schema Markup Validator](https://validator.schema.org/).

## Tracking and statistics

The frontend records three things: views per job and session, clicks on the
*Apply now* link, and every filter combination visitors submit. One switch in
the extension configuration turns all of it off. The backend shows the data in
a module group **Jobs**:

- **Jobs** — every job in the installation in one table, filterable by access
  state, with the view count in the last column.
- **Statistics** — top 5 by views and by applications, a table per job, the
  grouped filter combinations, and a *Reset all statistics* button.

With `typo3/cms-dashboard` installed, a **Job statistics** widget shows the two
charts on the dashboard. The apply link goes through a small proxy action, so
add `/{job-slug}/apply` to your route enhancer for speaking URLs; the
[documentation](Documentation/Tracking/Index.rst) has the full example. Only a
hash of the frontend session id is stored — no IP address, no user agent.

## Notes on the TYPO3 v14 baseline

This extension targets v14 only, which keeps it free of compatibility layers:

- Almost no `ext_tables.sql` — every record table, column and MM table is
  derived from TCA. Declared by hand are only the pair of geo coordinates (TCA
  type `number` with format `decimal` is hard-wired to two decimals, about a
  kilometre of error) and the three tracking tables, which have no TCA on
  purpose. `Tests/Functional/Database/SchemaTest.php` keeps both halves honest.
- No `ext_tables.php`, which is deprecated as of v14.3.
- Plugins are their own `CType`; `list_type` was removed in v14.0.
- Labels use v14 translation domains (`jobs.db:…`) instead of long `LLL:EXT:` paths.
- Configuration ships as a **site set**, not as static TypoScript includes.
- Relations, dates and structured-data values carry `l10n_mode: exclude`, so a
  translated job keeps the `hiringOrganization` and `jobLocation` Google requires
  instead of inheriting an empty translation.

## Development

The repository carries its own TYPO3 instance: DDEV, a development-only site
package, and a seed command that builds the demo content.

```bash
ddev start
```

```bash
ddev typo3-install
```

That is a full reset — it drops the database, installs TYPO3, creates the page
tree and demo records, and prints the URLs. Afterwards a plain `ddev start` is
enough; the database persists.

| | |
|---|---|
| Backend | `/typo3` — **`test` / `test`** |
| Job list | `/jobs` (English), `/de/stellenangebote` (German) |
| Job detail | `/jobs/detail/<slug>`, speaking URLs via route enhancer |

The demo data is deliberately varied: a job with a location and a salary range,
one with a fixed salary and no expiry date, a fully remote one without any
location, and one left untranslated so the German language fallback is visible.

**[DEVELOPMENT.md](DEVELOPMENT.md) documents the setup in full** — how the
pieces fit together, what the install command does step by step, how to extend
the demo data, and the handful of TYPO3 quirks that cost us a debugging round
each.

### Tests and static analysis

```bash
ddev composer ci
```

That runs, in order: PHP lint, coding standards, PHPStan level 8, unit tests and
functional tests. Functional tests default to SQLite and need no Docker, so
`composer test:functional` works straight from the host; CI runs the same suite
across PHP 8.2 to 8.5 and once more against MariaDB.

## License

GPL-2.0-or-later. Author: Bastian Schwabe.
