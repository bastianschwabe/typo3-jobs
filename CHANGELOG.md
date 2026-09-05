# Changelog

All notable changes to this extension are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versions follow
[Semantic Versioning](https://semver.org/).

## [0.2.0] – 2026-09-05

### Added

- Frontend design for list and detail view (`Resources/Public/Css/Jobs.css`):
  system font stack, neutral grays, container-query based layout, all colors
  and radii exposed as CSS custom properties on `.jobs`.
- Structural stylesheet `JobsBasic.css` without colors, fonts or rounded
  corners.
- Site setting `jobs.stylesheet` (`default`, `basic`, `none`) that selects
  which stylesheet the Fluid layout loads. Defaults to `default`.
- Detail view: summary card with salary, application deadline, reference and
  the apply button; contact, location and company as cards in a side column.
- Accessible labels for the pagination links.

### Changed

- The list item is now clickable as a whole; the "View job" link is hidden
  from assistive technology, because the title link already leads there.
- Remote positions are shown next to the locations instead of as a separate
  meta entry.
- The development site no longer carries its own demo CSS for the plugins.

## [0.1.0] – 2026-09-05

First public version.

### Added

- Job records with level, field of work, locations, contact persons and
  company profiles.
- Frontend plugins: filterable list (level, field of work, location,
  employment type, remote, free text) and detail view.
- `schema.org/JobPosting` JSON-LD on the detail page, including remote
  positions (`TELECOMMUTE`), salary ranges and `directApply`.
- Site set `bastianschwabe/jobs` with settings for storage folder, detail
  page, items per page and fallback organization.
- Tracking of job views per frontend session, clicks on the apply link
  (through a redirecting proxy action) and submitted filter combinations.
- Backend module group *Jobs* with a job overview (access state filter, view
  counts) and a statistics module (top 5 charts, per-job table, filter
  combinations, reset).
- Dashboard widget with the two top 5 charts when `EXT:dashboard` is
  installed.
- Extension configuration switch `tracking.enabled` that turns the whole
  tracking feature off.
- English and German labels for frontend and backend.
