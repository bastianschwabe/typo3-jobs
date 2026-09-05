:navigation-title: Jobs

..  _start:

====
Jobs
====

:Extension key:
    jobs

:Package name:
    bastianschwabe/jobs

:Version:
    |release|

:Language:
    en

:Author:
    Bastian Schwabe

:License:
    This document is published under the
    `Creative Commons BY 4.0 <https://creativecommons.org/licenses/by/4.0/>`__
    license.

----

Job postings for TYPO3: a filterable list, a detail view, and
`schema.org/JobPosting <https://schema.org/JobPosting>`__ structured data that
passes Google's Rich Results Test.

The data model *is* the schema.org vocabulary. Select values are PHP backed
enums whose backing values are the literal, case-sensitive schema.org values,
and the TCA items are generated from those enums, so an invalid value cannot be
stored in the first place.

----

..  card-grid::
    :columns: 1
    :columns-md: 2
    :gap: 4
    :class: pb-4
    :card-height: 100

    ..  card:: Installation

        Requirements, installation and the first plugin on a page.

        ..  card-footer:: :ref:`Install the extension <installation>`
            :button-style: btn btn-secondary stretched-link

    ..  card:: Configuration

        The site set, its settings and the plugin FlexForms.

        ..  card-footer:: :ref:`Configure the extension <configuration>`
            :button-style: btn btn-secondary stretched-link

    ..  card:: Structured data

        Which JobPosting properties are emitted, and how to validate them.

        ..  card-footer:: :ref:`Understand the JSON-LD <structured-data>`
            :button-style: btn btn-secondary stretched-link

    ..  card:: For editors

        The record types and the fields that matter for search engines.

        ..  card-footer:: :ref:`Maintain jobs <editors>`
            :button-style: btn btn-secondary stretched-link

    ..  card:: Tracking and statistics

        Views, application clicks and filter usage; the backend modules and
        the dashboard widget.

        ..  card-footer:: :ref:`Read the statistics <tracking>`
            :button-style: btn btn-secondary stretched-link

    ..  card:: Development

        The bundled DDEV instance, its demo content and the test suite.

        ..  card-footer:: :ref:`Set up the instance <development>`
            :button-style: btn btn-secondary stretched-link

..  toctree::
    :hidden:

    Installation/Index
    Configuration/Index
    StructuredData/Index
    Editors/Index
    Tracking/Index
    Development/Index
