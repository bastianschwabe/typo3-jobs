..  _configuration:

=============
Configuration
=============

Site set settings
=================

All settings live in the :guilabel:`Jobs` site set and can be edited per site in
:guilabel:`Site Management > Settings`.

..  confval-menu::
    :name: jobs-settings

    ..  confval:: jobs.storagePid
        :type: string
        :default: (empty)

        Comma separated page IDs the job records are stored on.

    ..  confval:: jobs.detailPid
        :type: int
        :default: 0

        Page containing the detail plugin. Used to link from the list.

    ..  confval:: jobs.itemsPerPage
        :type: int
        :default: 10

        Number of jobs per page in the list view.

    ..  confval:: jobs.stylesheet
        :type: string
        :default: default

        Which stylesheet the plugins load. See :ref:`styling`.

        ``default``
            The shipped design: system fonts, neutral grays, adjustable
            through CSS custom properties.
        ``basic``
            A structural stylesheet only. No colors, fonts or rounded
            corners; borders use the site's text color.
        ``none``
            No stylesheet at all. The markup stays the same.

    ..  confval:: jobs.organization.name
        :type: string
        :default: (empty)

        Fallback :sql:`hiringOrganization` name, used when a job has no company
        profile assigned. Google requires this property, so setting it is
        strongly recommended even if you always assign a profile.

    ..  confval:: jobs.organization.url
        :type: string
        :default: (empty)

        Fallback organization URL.

    ..  confval:: jobs.organization.logo
        :type: string
        :default: (empty)

        Absolute URL of the company logo.

Plugin options
==============

Both plugins carry a FlexForm that overrides the site settings per instance.

**Jobs: List**

*   :guilabel:`Detail page` — overrides :confval:`jobs.detailPid`.
*   :guilabel:`Jobs per page` — ``0`` keeps the value from the site settings.
*   :guilabel:`Restrict to level / field of work / location` — pre-filters the
    list, for example to show only one department on a landing page.

**Jobs: Detail**

*   :guilabel:`List page` — target of the back link.

Filtering
=========

The list filter is a plain :html:`GET` form. The query string stays readable and
cacheable, and the list works without JavaScript:

..  code-block:: text

    /jobs?tx_jobs_list[demand][occupationalField]=3&tx_jobs_list[demand][remoteOnly]=1

..  important::
    Because of this the list action is registered as **non-cacheable** and its
    parameters are excluded from the cache hash. TYPO3 rejects cache-relevant
    query parameters that carry no ``cHash``, and a plain :html:`GET` form
    cannot produce one. The page itself stays cached; only the plugin is
    rendered per request.

    The alternative would be a route enhancer that maps each filter into the
    path, which keeps the list cacheable at the price of a much larger route
    configuration. That is a worthwhile addition once the filter set is stable.

..  _styling:

Styling
=======

The extension ships two stylesheets under :file:`Resources/Public/Css/`. Which
one is loaded is decided by :confval:`jobs.stylesheet`; the Fluid layout adds
it through :html:`<f:asset.css>`, so it only appears on pages that contain one
of the plugins and is emitted once even when list and detail sit on the same
page.

Default design
--------------

:file:`Jobs.css` is meant to drop into an existing site without a fight: it
uses the system font stack, neutral grays and no external assets. All colors,
radii and the font are CSS custom properties on the :css:`.jobs` wrapper, so a
site adjusts them without overriding selectors:

..  code-block:: css

    .jobs {
        --jobs-font-family: inherit;       /* use the site's font */
        --jobs-color-accent: #0a3d62;      /* buttons, current page */
        --jobs-color-on-accent: #ffffff;
        --jobs-radius: 0;                  /* square corners */
    }

The complete list of properties is at the top of :file:`Jobs.css`:

..  list-table::
    :header-rows: 1

    *   -   Property
        -   Used for
    *   -   ``--jobs-font-family``, ``--jobs-font-size``, ``--jobs-line-height``
        -   Typography of the whole plugin output
    *   -   ``--jobs-color-text``, ``--jobs-color-muted``, ``--jobs-color-faint``
        -   Text, secondary text, placeholders and separators
    *   -   ``--jobs-color-border``, ``--jobs-color-border-strong``
        -   Card borders, dividers, form field borders
    *   -   ``--jobs-color-surface``, ``--jobs-color-surface-raised``, ``--jobs-color-hover``
        -   Filter and summary card background, list background, hover state
    *   -   ``--jobs-color-accent``, ``--jobs-color-on-accent``, ``--jobs-color-focus``
        -   Buttons, current pagination page, focus rings
    *   -   ``--jobs-radius``, ``--jobs-radius-small``
        -   Cards and list container; form fields, buttons and badges

Layout breakpoints are container queries on :css:`.jobs`, so the two-column
detail view and the four-column filter switch on based on the width of the
column the plugin is placed in, not on the viewport.

Structural stylesheet
---------------------

:file:`JobsBasic.css` only arranges things: the filter fields wrap in a row,
the meta data sits in a line, the detail sections have some spacing. Borders
are :css:`1px solid` in the current text color, there are no rounded corners,
colors or font settings. Use it when the site brings its own visual language
and you only want the plugin to be laid out sensibly.

No stylesheet
-------------

With ``none`` nothing is loaded. The markup uses BEM-style class names
(:css:`.jobs-filter__field`, :css:`.job-teaser__title`,
:css:`.job-detail__card`, ...) that are stable across both shipped sheets, so a
site stylesheet can target them directly.

Overriding templates
====================

Add your own paths with a higher index in your site package:

..  code-block:: typoscript

    plugin.tx_jobs.view {
        templateRootPaths.10 = EXT:my_sitepackage/Resources/Private/Extensions/Jobs/Templates/
        partialRootPaths.10 = EXT:my_sitepackage/Resources/Private/Extensions/Jobs/Partials/
        layoutRootPaths.10 = EXT:my_sitepackage/Resources/Private/Extensions/Jobs/Layouts/
    }

The stylesheet is loaded in :file:`Layouts/Default.html`. An overridden layout
that leaves out the :html:`<f:switch>` block loads nothing, which is the same
as setting :confval:`jobs.stylesheet` to ``none``.
