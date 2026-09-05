# Changelog

All notable changes to this extension are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versions follow
[Semantic Versioning](https://semver.org/).

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
