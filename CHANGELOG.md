# Changelog

All notable changes to this extension are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versions follow
[Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added

- Site setting `jobs.applicationFormPid`: the apply button leads to an EXT:form
  application form instead of the application URL or e-mail address. The job
  is handed over as `tx_jobs_apply[job]`; the click is recorded as type `FORM`.
- Optional EXT:form integration (only active where `typo3/cms-form` is
  installed): the hidden element `jobUid` and the optional text element
  `jobTitle` are pre-filled, and an email finisher with the option
  `jobsRecipient: true` sends the application to the job's application e-mail
  address, or to its first contact with an address, falling back to the
  recipients of the form definition. `jobsReplyTo: true` does the same for the
  reply-to address of the confirmation mail to the applicant.
- Shipped example form `Resources/Private/Forms/job-application.form.yaml`,
  registered with EXT:form through the form set
  `Configuration/Form/ApplicationForm/config.yaml`: it is offered read-only in
  the form manager and can be selected in the form plugin as it is. Its German
  translation ships as `Resources/Private/Language/Form/de.locallang.xlf`, the
  way EXT:form translates a form definition.

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
