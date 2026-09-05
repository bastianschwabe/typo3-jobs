..  _structured-data:

===============
Structured data
===============

The detail view emits one :html:`<script type="application/ld+json">` block
describing the job as a `schema.org/JobPosting <https://schema.org/JobPosting>`__.

Design rules
============

**Optional properties are omitted, never emitted empty.**
Google treats an empty value as malformed, while a missing one is merely absent.

**Select values are the schema.org values.**
:php:`EmploymentType`, :php:`SalaryUnit` and :php:`EducationCategory` are PHP
backed enums whose backing values are the literal, case-sensitive schema.org
values. The TCA items are generated from the enums, so the database can only
ever hold a valid value. Values that are no longer part of the vocabulary are
dropped when building the JSON-LD instead of being passed through.

**Angle brackets and ampersands are escaped.**
The job description is editor-supplied HTML. It is encoded with
:php:`JSON_HEX_TAG` and :php:`JSON_HEX_AMP` so it cannot terminate the script
element.

Property coverage
=================

..  list-table::
    :header-rows: 1

    *   -   Property
        -   Source
        -   Status
    *   -   :sql:`title`
        -   Job title
        -   required
    *   -   :sql:`description`
        -   Job description (RTE)
        -   required
    *   -   :sql:`datePosted`
        -   Published on
        -   required
    *   -   :sql:`hiringOrganization`
        -   First assigned company profile, else the site settings fallback
        -   required
    *   -   :sql:`jobLocation`
        -   Assigned locations, as :sql:`Place` with :sql:`PostalAddress` and :sql:`GeoCoordinates`
        -   required
    *   -   :sql:`validThrough`
        -   Valid through, emitted as end of that day
        -   recommended
    *   -   :sql:`employmentType`
        -   Employment type
        -   recommended
    *   -   :sql:`baseSalary`
        -   Salary fields, as fixed value or min/max range
        -   recommended
    *   -   :sql:`jobLocationType`
        -   :guilabel:`Fully remote`, emitted as ``TELECOMMUTE``
        -   remote jobs
    *   -   :sql:`applicantLocationRequirements`
        -   Eligible countries
        -   remote jobs
    *   -   :sql:`identifier`
        -   Job ID, as :sql:`PropertyValue`
        -   recommended
    *   -   :sql:`educationRequirements`
        -   Education level as :sql:`EducationalOccupationalCredential`, else free text
        -   recommended
    *   -   :sql:`experienceRequirements`
        -   Experience in months as :sql:`OccupationalExperienceRequirements`, else free text
        -   recommended
    *   -   :sql:`industry`, :sql:`occupationalCategory`
        -   Job, falling back to its field of work
        -   recommended
    *   -   :sql:`directApply`
        -   :guilabel:`Direct application`
        -   optional
    *   -   :sql:`image`, :sql:`url`
        -   Job images and the canonical detail URL
        -   optional

Inheritance
===========

Editors should not have to repeat themselves, so some values are inherited:

*   :guilabel:`Experience in months` falls back to the value on the assigned
    :guilabel:`Level`.
*   :guilabel:`Industry` and :guilabel:`Occupational category` fall back to the
    assigned :guilabel:`Field of work`.
*   :sql:`hiringOrganization` falls back to the organization in the site
    settings when no company profile is assigned.

Remote jobs
===========

For a fully remote job, tick :guilabel:`Fully remote` **and** choose at least
one country under :guilabel:`Eligible countries`. Google requires at least one
country, supplied either through :sql:`applicantLocationRequirements` or through
a location — a remote job with neither is rejected.

Validation
==========

Check a rendered detail page with:

*   `Rich Results Test <https://search.google.com/test/rich-results>`__
*   `Schema Markup Validator <https://validator.schema.org/>`__

Test at least three cases: a job with a location, a fully remote job, and a job
without a salary.

Extending the output
====================

:php:`\BastianSchwabe\Jobs\Service\JobPostingSchemaBuilder` builds the array and
is a plain, dependency-free service. Decorate it through
:file:`Configuration/Services.yaml` to add properties of your own.
